# DNS-as-Code — Zone Templates

> **This directory is a template.** Fork or copy it into your own dedicated zones repository and manage your DNS records as code.

Powered by [example-dns](https://github.com/example-dns/example-dns) — open-source authoritative DNS infrastructure.

---

## Overview

The DNS-as-Code pattern lets you manage DNS records the same way you manage software:

- Zone definitions live in **YAML files** under version control
- Every `git push` triggers an automated sync to your PowerDNS instance via the REST API
- Diffs show exactly what changed, PRs allow review before applying

The **sync tooling** (`infra/scripts/sync-zones.py`) lives in the main `example-dns` repo. Your **zone files** live here — in your own repo, forked from this template.

---

## Getting Started

### 1. Fork / copy this directory

Create a new Git repository (on GitHub, Codeberg, or any platform) and copy this `zones/` directory as the root of that repo. You should end up with:

```
your-zones-repo/
├── README.md                  ← this file
├── .github/
│   └── workflows/
│       └── sync-dns.yml       ← copied from example-dns/.github/workflows/sync-dns.yml
├── example-dns.com.yml        ← rename/replace with your domain(s)
└── yourdomain.com.yml
```

### 2. Write your zone files

Each domain gets one `.yml` file. See the [YAML Format](#yaml-format) section below.

### 3. Configure CI secrets

Add two secrets to your repository:

| Secret name      | Value                                         |
|------------------|-----------------------------------------------|
| `PDNS_API_URL`   | URL of your PowerDNS HTTP API, e.g. `http://ns1.yourdomain.com:8081` |
| `PDNS_API_KEY`   | The `api-key` value from your `pdns.conf`     |

> [!IMPORTANT]
> The PowerDNS API should **never** be exposed to the public internet. Use a VPN, private network, or SSH tunnel. See the [security section in docs/dns-as-code.md](https://github.com/example-dns/example-dns/blob/master/docs/dns-as-code.md#security-considerations).

### 4. Add the workflow file

Copy `.github/workflows/sync-dns.yml` from the main `example-dns` repo into your zones repo. On push to `main`/`master`, the sync runs automatically.

For Codeberg / Woodpecker CI, use the `.woodpecker.yml` snippet (embedded as a comment inside `sync-dns.yml`).

---

## YAML Format

Each zone file must be named `<domain>.yml` (e.g. `example-dns.com.yml`).

### Top-level fields

| Field         | Type    | Required | Description                                                   |
|---------------|---------|----------|---------------------------------------------------------------|
| `zone`        | string  | ✅ yes   | The fully-qualified domain name of the zone (without trailing dot) |
| `ttl_default` | integer | ✅ yes   | Default TTL (seconds) applied to records that omit `ttl`      |
| `records`     | list    | ✅ yes   | List of DNS record objects (see below)                        |

### Record fields

| Field      | Type    | Required              | Description                                                                  |
|------------|---------|-----------------------|------------------------------------------------------------------------------|
| `name`     | string  | ✅ yes                | Record name. Use `@` for the zone apex. Relative names (e.g. `www`) are expanded to FQDNs automatically. |
| `type`     | string  | ✅ yes                | DNS record type: `A`, `AAAA`, `CNAME`, `MX`, `NS`, `TXT`, `SOA`, `SRV`, `CAA`, `PTR`, etc. |
| `content`  | string  | ✅ yes                | Record data in standard zone-file format. For `TXT` records, wrap the value in double quotes within a YAML single-quoted string (e.g. `'"v=spf1 mx ~all"'`). |
| `ttl`      | integer | ❌ no                 | Per-record TTL override. Falls back to `ttl_default` if omitted.             |
| `priority` | integer | ❌ no (MX/SRV only)  | Priority value for `MX` and `SRV` records. Also accepted as `prio`.         |
| `disabled` | boolean | ❌ no                 | Set to `true` to push the record to PowerDNS in disabled state (default: `false`). |

### Multiple values for the same name + type

Records with the same `name` and `type` are automatically grouped into a single RRset. Just list them separately:

```yaml
records:
  - name: "@"
    type: NS
    content: "ns1.example-dns.net."

  - name: "@"
    type: NS
    content: "ns2.example-dns.org."
```

### Full example

```yaml
zone: yourdomain.com
ttl_default: 3600

records:
  - name: "@"
    type: SOA
    content: "ns1.example-dns.net. hostmaster.yourdomain.com. 2024010101 10800 3600 604800 3600"

  - name: "@"
    type: NS
    content: "ns1.example-dns.net."

  - name: "@"
    type: NS
    content: "ns2.example-dns.org."

  - name: "@"
    type: A
    ttl: 300
    content: "203.0.113.1"

  - name: "www"
    type: CNAME
    content: "yourdomain.com."

  - name: "@"
    type: MX
    priority: 10
    content: "mail.yourdomain.com."

  - name: "mail"
    type: A
    content: "203.0.113.2"

  - name: "@"
    type: TXT
    content: '"v=spf1 mx ~all"'

  - name: "_dmarc"
    type: TXT
    content: '"v=DMARC1; p=reject; rua=mailto:dmarc@yourdomain.com"'

  - name: "_acme-challenge"
    type: TXT
    ttl: 60
    content: '"<your-acme-token>"'
```

---

## Running the Sync Manually

You can invoke the sync script locally for testing:

```bash
# Install dependencies
pip install requests PyYAML

# Dry run (no API calls, shows planned changes)
python path/to/example-dns/infra/scripts/sync-zones.py \
  --zones-dir ./zones \
  --api-url http://localhost:8081 \
  --api-key your-api-key \
  --dry-run

# Sync only one zone
python path/to/example-dns/infra/scripts/sync-zones.py \
  --zones-dir ./zones \
  --api-url http://localhost:8081 \
  --api-key your-api-key \
  --zone yourdomain.com

# Full sync
python path/to/example-dns/infra/scripts/sync-zones.py \
  --zones-dir ./zones \
  --api-url http://localhost:8081 \
  --api-key your-api-key
```

---

## Tips & Conventions

- **One file per domain.** Keep zones separate for clarity and targeted diffs.
- **Increment your SOA serial** on every change — the sync script sends the full SOA content as provided.
- **DNSSEC records** (`NSEC`, `NSEC3`, `RRSIG`, `DNSKEY`) are never deleted by the sync script to preserve auto-signed records.
- **Use `--dry-run`** in PR checks to validate YAML before merging to `main`.
- **Pin your serial format** to `YYYYMMDDnn` (e.g. `2024010101`) for human readability.

---

## Further Reading

- [Full DNS-as-Code documentation](https://github.com/example-dns/example-dns/blob/master/docs/dns-as-code.md)
- [Sync script reference](https://github.com/example-dns/example-dns/blob/master/infra/scripts/sync-zones.py)
- [example-dns project](https://github.com/example-dns/example-dns)
- [PowerDNS REST API docs](https://doc.powerdns.com/authoritative/http-api/zone.html)
