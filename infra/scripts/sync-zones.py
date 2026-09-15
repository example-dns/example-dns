#!/usr/bin/env python3
"""
sync-zones.py — DNS-as-Code zone sync tool for PowerDNS
========================================================
Part of the example-dns project: https://github.com/example-dns/example-dns

REQUIREMENTS (see sync-zones-requirements.txt):
    pip install requests PyYAML

USAGE:
    python3 sync-zones.py --api-url http://localhost:8081 --api-key YOUR_KEY
    python3 sync-zones.py --dry-run
    python3 sync-zones.py --zone example-dns.com

This script reads YAML zone files from a zones directory and syncs them
to a PowerDNS Authoritative Server via the REST API. It:
  - Creates zones that don't yet exist in PowerDNS
  - Replaces (PATCH) all desired RRsets
  - Deletes RRsets present in PowerDNS but absent from YAML
    (DNSSEC-managed types are never deleted)
"""

import argparse
import os
import sys
import glob
import json

import yaml        # PyYAML
import requests    # requests

# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------

# PowerDNS API base path (version segment)
PDNS_API_BASE = "/api/v1/servers/localhost"

# Record types managed automatically by DNSSEC — never delete these.
DNSSEC_TYPES = {"NSEC", "NSEC3", "RRSIG", "DNSKEY", "CDNSKEY", "CDS"}

# ANSI color codes for terminal output
_RESET  = "\033[0m"
_GREEN  = "\033[32m"
_YELLOW = "\033[33m"
_CYAN   = "\033[36m"
_RED    = "\033[31m"
_BOLD   = "\033[1m"


# ---------------------------------------------------------------------------
# Logging helpers
# ---------------------------------------------------------------------------

def _color(text: str, code: str) -> str:
    """Wrap text in an ANSI color code if stdout is a TTY."""
    if sys.stdout.isatty():
        return f"{code}{text}{_RESET}"
    return text


def log_created(zone: str, detail: str = "") -> None:
    prefix = _color("✓ created", _GREEN)
    print(f"  {prefix}  {zone}{('  ' + detail) if detail else ''}")


def log_updated(zone: str, detail: str = "") -> None:
    prefix = _color("~ updated", _YELLOW)
    print(f"  {prefix}  {zone}{('  ' + detail) if detail else ''}")


def log_unchanged(zone: str) -> None:
    prefix = _color("= unchanged", _CYAN)
    print(f"  {prefix}  {zone}")


def log_deleted(zone: str, detail: str = "") -> None:
    prefix = _color("✗ deleted", _RED)
    print(f"  {prefix}  {zone}{('  ' + detail) if detail else ''}")


def log_error(msg: str) -> None:
    print(_color(f"  ERROR: {msg}", _RED), file=sys.stderr)


def log_dryrun(msg: str) -> None:
    prefix = _color("[dry-run]", _BOLD)
    print(f"  {prefix} {msg}")


# ---------------------------------------------------------------------------
# Name normalization helpers
# ---------------------------------------------------------------------------

def normalize_name(name: str, zone_fqdn: str) -> str:
    """
    Normalize a record name to a fully-qualified DNS name (with trailing dot).

    Rules:
      '@'                → zone apex (e.g. "example-dns.com.")
      'www'              → "www.example-dns.com."
      'www.example.com.' → returned as-is (already absolute)
      'www.example.com'  → "www.example.com." (trailing dot added)
    """
    # Ensure the zone FQDN itself always ends with a dot
    if not zone_fqdn.endswith("."):
        zone_fqdn += "."

    if name == "@":
        return zone_fqdn

    if name.endswith("."):
        # Already absolute
        return name

    # Check if it's already a full name relative to the zone
    # (e.g. user wrote "sub.example-dns.com" without trailing dot)
    without_dot = zone_fqdn.rstrip(".")
    if name.endswith(without_dot):
        return name + "."

    # Relative name — append zone
    return f"{name}.{zone_fqdn}"


def zone_fqdn(zone_name: str) -> str:
    """Return zone name guaranteed to have a trailing dot."""
    return zone_name if zone_name.endswith(".") else zone_name + "."


# ---------------------------------------------------------------------------
# YAML loading
# ---------------------------------------------------------------------------

def load_zone_file(path: str) -> dict:
    """Load and minimally validate a single YAML zone file."""
    with open(path, "r", encoding="utf-8") as fh:
        data = yaml.safe_load(fh)

    if not isinstance(data, dict):
        raise ValueError(f"Zone file must be a YAML mapping, got: {type(data)}")
    for required in ("zone", "ttl_default", "records"):
        if required not in data:
            raise ValueError(f"Missing required field '{required}' in {path}")
    if not isinstance(data["records"], list):
        raise ValueError(f"'records' must be a list in {path}")

    return data


def resolve_zones_dir(zones_dir: str) -> str:
    """
    Resolve the effective zones directory.

    Auto-detection order (only applied when zones_dir is the sentinel '.'):
      1. ./zones/   — subdirectory layout  (zones repo with a zones/ folder)
      2. ./          — flat/root layout     (zone files directly at repo root)

    When the user passes an explicit --zones-dir the value is used as-is.
    """
    if zones_dir != ".":
        return zones_dir

    # Auto-detect: prefer a zones/ subdirectory if it exists and has .yml files
    candidate = os.path.join(".", "zones")
    if os.path.isdir(candidate) and glob.glob(os.path.join(candidate, "*.yml")):
        return candidate

    # Fall back to the current working directory (root-layout repos)
    return "."


def discover_zone_files(zones_dir: str, zone_filter: str | None) -> list[str]:
    """Return sorted list of .yml paths in zones_dir, optionally filtered."""
    pattern = os.path.join(zones_dir, "*.yml")
    paths = sorted(glob.glob(pattern))
    if not paths:
        raise FileNotFoundError(f"No .yml files found in: {zones_dir}")
    if zone_filter:
        # Allow match with or without trailing dot
        target = zone_filter.rstrip(".")
        paths = [
            p for p in paths
            if os.path.splitext(os.path.basename(p))[0] == target
        ]
        if not paths:
            raise FileNotFoundError(
                f"No zone file found for '{zone_filter}' in {zones_dir}"
            )
    return paths


# ---------------------------------------------------------------------------
# RRset building
# ---------------------------------------------------------------------------

def build_rrsets(zone_data: dict) -> list[dict]:
    """
    Convert the YAML 'records' list into a list of PowerDNS RRset dicts.

    Records with the same (name, type) are merged into a single RRset.
    The 'priority' field (also accepted as 'prio') is prepended to the
    content string for MX and SRV records, as required by PowerDNS.
    """
    default_ttl = int(zone_data["ttl_default"])
    zone_name   = zone_fqdn(zone_data["zone"])

    # Group records by (normalized_name, type) — preserve insertion order
    rrsets: dict[tuple[str, str], dict] = {}

    for rec in zone_data["records"]:
        # --- Resolve name ---
        raw_name = str(rec["name"])
        rtype    = str(rec["type"]).upper()
        fqname   = normalize_name(raw_name, zone_name)

        # --- Resolve TTL ---
        ttl = int(rec.get("ttl", default_ttl))

        # --- Resolve content ---
        content  = str(rec["content"])
        priority = rec.get("priority", rec.get("prio"))

        # PowerDNS expects MX/SRV content prefixed with the priority weight,
        # e.g. "10 mail.example.com." for MX records.
        if rtype in ("MX", "SRV") and priority is not None:
            content = f"{int(priority)} {content}"

        disabled = bool(rec.get("disabled", False))

        key = (fqname, rtype)
        if key not in rrsets:
            rrsets[key] = {
                "name":       fqname,
                "type":       rtype,
                "ttl":        ttl,
                "changetype": "REPLACE",
                "records":    [],
            }
        # If multiple records share the same key, use the TTL of the first one.
        rrsets[key]["records"].append({
            "content":  content,
            "disabled": disabled,
        })

    return list(rrsets.values())


# ---------------------------------------------------------------------------
# PowerDNS API client
# ---------------------------------------------------------------------------

class PDNSClient:
    """Thin wrapper around the PowerDNS HTTP REST API."""

    def __init__(self, api_url: str, api_key: str, timeout: int = 30) -> None:
        # Strip trailing slash for consistent URL construction
        self.base = api_url.rstrip("/") + PDNS_API_BASE
        self.session = requests.Session()
        self.session.headers.update({
            "X-API-Key":    api_key,
            "Content-Type": "application/json",
            "Accept":       "application/json",
        })
        self.timeout = timeout

    def _url(self, path: str) -> str:
        return self.base + path

    def list_zones(self) -> list[dict]:
        """Return list of zone objects from the API."""
        resp = self.session.get(self._url("/zones"), timeout=self.timeout)
        resp.raise_for_status()
        return resp.json()

    def get_zone(self, zone_id: str) -> dict:
        """Fetch full zone detail (including rrsets) for zone_id."""
        resp = self.session.get(
            self._url(f"/zones/{zone_id}"), timeout=self.timeout
        )
        resp.raise_for_status()
        return resp.json()

    def create_zone(self, name_fqdn: str) -> dict:
        """Create a new Native zone in PowerDNS."""
        payload = {
            "name":        name_fqdn,
            "kind":        "Native",
            "nameservers": [],
        }
        resp = self.session.post(
            self._url("/zones"),
            data=json.dumps(payload),
            timeout=self.timeout,
        )
        resp.raise_for_status()
        return resp.json()

    def patch_rrsets(self, zone_id: str, rrsets: list[dict]) -> None:
        """PATCH a batch of RRsets (REPLACE or DELETE changeType)."""
        payload = {"rrsets": rrsets}
        resp = self.session.patch(
            self._url(f"/zones/{zone_id}"),
            data=json.dumps(payload),
            timeout=self.timeout,
        )
        resp.raise_for_status()


# ---------------------------------------------------------------------------
# Core sync logic
# ---------------------------------------------------------------------------

def rrset_key(rrset: dict) -> tuple[str, str]:
    """Return (name, type) tuple as a hashable key."""
    return (rrset["name"], rrset["type"])


def rrsets_equal(desired: dict, current: dict) -> bool:
    """
    Compare a desired RRset (from YAML) to a current one (from API).
    Returns True if TTL and all record contents match exactly.
    """
    if desired["ttl"] != current.get("ttl"):
        return False
    desired_records = sorted(r["content"] for r in desired["records"])
    current_records = sorted(
        r["content"] for r in current.get("records", [])
    )
    return desired_records == current_records


def sync_zone(
    zone_data: dict,
    client: PDNSClient,
    existing_zones: dict[str, dict],
    dry_run: bool,
) -> bool:
    """
    Sync a single zone from YAML data to PowerDNS.

    Returns True on success, False if an error occurred.
    """
    zone_name = zone_fqdn(zone_data["zone"])
    print(_color(f"\n  Zone: {zone_name}", _BOLD))

    try:
        desired_rrsets = build_rrsets(zone_data)
    except (KeyError, ValueError) as exc:
        log_error(f"Failed to build RRsets for {zone_name}: {exc}")
        return False

    # ------------------------------------------------------------------ #
    # 1. Check whether the zone already exists in PowerDNS
    # ------------------------------------------------------------------ #
    zone_exists = zone_name in existing_zones

    if not zone_exists:
        # Create the zone
        if dry_run:
            log_dryrun(f"Would create zone: {zone_name}")
        else:
            try:
                client.create_zone(zone_name)
                log_created(zone_name, "(zone)")
            except requests.HTTPError as exc:
                log_error(f"Could not create zone {zone_name}: {exc}")
                return False

        # No existing RRsets to compare — push everything
        if dry_run:
            for rs in desired_rrsets:
                log_dryrun(
                    f"Would add RRset {rs['name']} {rs['type']} "
                    f"({len(rs['records'])} record(s))"
                )
            return True

        # PATCH all desired RRsets into the freshly created zone
        try:
            client.patch_rrsets(zone_name, desired_rrsets)
            log_created(zone_name, f"({len(desired_rrsets)} RRset(s) added)")
        except requests.HTTPError as exc:
            log_error(f"Could not populate RRsets for {zone_name}: {exc}")
            return False
        return True

    # ------------------------------------------------------------------ #
    # 2. Zone already exists — compute diff
    # ------------------------------------------------------------------ #
    # Fetch full zone detail to get current RRsets
    try:
        if dry_run:
            # Still fetch to show meaningful diff
            current_zone = client.get_zone(zone_name)
        else:
            current_zone = client.get_zone(zone_name)
    except requests.HTTPError as exc:
        log_error(f"Could not fetch zone detail for {zone_name}: {exc}")
        return False

    current_rrsets: dict[tuple[str, str], dict] = {
        rrset_key(rs): rs for rs in current_zone.get("rrsets", [])
    }
    desired_map: dict[tuple[str, str], dict] = {
        rrset_key(rs): rs for rs in desired_rrsets
    }

    # --- Build REPLACE list (desired RRsets that differ or are new) ---
    to_replace: list[dict] = []
    unchanged_count = 0

    for key, desired in desired_map.items():
        current = current_rrsets.get(key)
        if current is None:
            # New RRset not yet in PowerDNS
            to_replace.append(desired)
        elif not rrsets_equal(desired, current):
            # Exists but content/TTL differs
            to_replace.append(desired)
        else:
            unchanged_count += 1

    # --- Build DELETE list (API RRsets absent from YAML, skip DNSSEC) ---
    to_delete: list[dict] = []
    for key, current in current_rrsets.items():
        rtype = key[1]
        if rtype in DNSSEC_TYPES:
            # Never delete DNSSEC-managed record types
            continue
        if key not in desired_map:
            to_delete.append({
                "name":       current["name"],
                "type":       current["type"],
                "changetype": "DELETE",
            })

    # --- Apply changes ---
    all_patches = to_replace + to_delete

    if not all_patches:
        log_unchanged(zone_name)
        return True

    if dry_run:
        for rs in to_replace:
            log_dryrun(
                f"Would replace RRset {rs['name']} {rs['type']} "
                f"({len(rs['records'])} record(s))"
            )
        for rs in to_delete:
            log_dryrun(f"Would delete RRset {rs['name']} {rs['type']}")
        return True

    try:
        client.patch_rrsets(zone_name, all_patches)
    except requests.HTTPError as exc:
        log_error(f"PATCH failed for {zone_name}: {exc}")
        return False

    # Report outcome
    if to_replace:
        log_updated(
            zone_name,
            f"({len(to_replace)} RRset(s) replaced, "
            f"{len(to_delete)} deleted, "
            f"{unchanged_count} unchanged)"
        )
    elif to_delete:
        log_deleted(
            zone_name,
            f"({len(to_delete)} stale RRset(s) removed)"
        )
    return True


# ---------------------------------------------------------------------------
# CLI entry point
# ---------------------------------------------------------------------------

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        prog="sync-zones.py",
        description=(
            "DNS-as-Code sync tool for PowerDNS.\n"
            "Reads YAML zone files and syncs them to the PowerDNS REST API.\n"
            "\n"
            "Dependencies: pip install requests PyYAML\n"
            "Full docs: https://github.com/example-dns/example-dns/blob/master/docs/dns-as-code.md"
        ),
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )

    parser.add_argument(
        "--zones-dir",
        default=".",
        metavar="DIR",
        help=(
            "Directory containing .yml zone files. "
            "Defaults to auto-detect: uses ./zones/ if it exists and contains "
            ".yml files, otherwise falls back to the current directory (root layout)."
        ),
    )
    parser.add_argument(
        "--api-url",
        default="http://localhost:8081",
        metavar="URL",
        help="PowerDNS API base URL (default: http://localhost:8081)",
    )
    parser.add_argument(
        "--api-key",
        default=os.environ.get("PDNS_API_KEY", ""),
        metavar="KEY",
        help=(
            "PowerDNS API key. "
            "Can also be set via the PDNS_API_KEY environment variable."
        ),
    )
    parser.add_argument(
        "--zone",
        default=None,
        metavar="ZONE",
        help="Sync only this specific zone (e.g. example-dns.com)",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Show planned changes without making any API calls (read-only)",
    )
    parser.add_argument(
        "--timeout",
        type=int,
        default=30,
        metavar="SECONDS",
        help="HTTP request timeout in seconds (default: 30)",
    )

    return parser.parse_args()


def main() -> int:
    args = parse_args()

    # ------------------------------------------------------------------ #
    # Resolve the effective zones directory (auto-detect if not explicit)
    # ------------------------------------------------------------------ #
    zones_dir = resolve_zones_dir(args.zones_dir)

    # ------------------------------------------------------------------ #
    # Validate required arguments
    # ------------------------------------------------------------------ #
    if not args.api_key and not args.dry_run:
        # In dry-run mode we still need to query the API (to diff),
        # but we surface the missing key error here for clarity.
        print(
            _color(
                "ERROR: --api-key is required (or set PDNS_API_KEY env var).",
                _RED,
            ),
            file=sys.stderr,
        )
        return 1

    if not os.path.isdir(zones_dir):
        print(
            _color(f"ERROR: zones directory not found: {zones_dir}", _RED),
            file=sys.stderr,
        )
        return 1

    # ------------------------------------------------------------------ #
    # Discover zone files
    # ------------------------------------------------------------------ #
    try:
        zone_files = discover_zone_files(zones_dir, args.zone)
    except FileNotFoundError as exc:
        print(_color(f"ERROR: {exc}", _RED), file=sys.stderr)
        return 1

    print(
        _color(
            f"\n{'[DRY RUN] ' if args.dry_run else ''}"
            f"Syncing {len(zone_files)} zone file(s) from {zones_dir}/ → {args.api_url}",
            _BOLD,
        )
    )

    # ------------------------------------------------------------------ #
    # Build API client and fetch existing zones list (one request)
    # ------------------------------------------------------------------ #
    client = PDNSClient(
        api_url=args.api_url,
        api_key=args.api_key,
        timeout=args.timeout,
    )

    # In dry-run mode with no API key we still try; the API key check above
    # will catch the empty-key case for non-dry-run.
    try:
        existing_zones_list = client.list_zones()
    except requests.ConnectionError as exc:
        print(_color(f"\nERROR: Cannot connect to PowerDNS API: {exc}", _RED), file=sys.stderr)
        return 1
    except requests.HTTPError as exc:
        print(_color(f"\nERROR: PowerDNS API returned an error: {exc}", _RED), file=sys.stderr)
        return 1

    # Build a lookup dict keyed by zone FQDN (with trailing dot)
    existing_zones: dict[str, dict] = {
        z["name"]: z for z in existing_zones_list
    }

    # ------------------------------------------------------------------ #
    # Sync each zone
    # ------------------------------------------------------------------ #
    success_count = 0
    error_count   = 0

    for zone_path in zone_files:
        try:
            zone_data = load_zone_file(zone_path)
        except (yaml.YAMLError, ValueError, OSError) as exc:
            log_error(f"Failed to load {zone_path}: {exc}")
            error_count += 1
            continue

        ok = sync_zone(zone_data, client, existing_zones, dry_run=args.dry_run)
        if ok:
            success_count += 1
        else:
            error_count += 1

    # ------------------------------------------------------------------ #
    # Summary
    # ------------------------------------------------------------------ #
    print(
        _color(
            f"\n  Done: {success_count} zone(s) synced"
            + (f", {error_count} error(s)" if error_count else "")
            + (" [dry-run, no changes applied]" if args.dry_run else ""),
            _BOLD,
        )
    )

    return 0 if error_count == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
