#!/usr/bin/env bash
# ==============================================================================
# example-dns VPS Installer
# PowerDNS Authoritative Server Bare-Metal / VPS Provisioning Script
#
# Supports Debian and Ubuntu LTS distributions.
# Provisions either Primary (example-dns.net) or Secondary (example-dns.org).
#
# Usage:
#   sudo bash install-vps.sh --role primary [--api-key <secret>] [--db-dir <dir>]
#   sudo bash install-vps.sh --role secondary [--db-dir <dir>]
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

# Defaults
ROLE=""
API_KEY="${PDNS_API_KEY:-}"
DB_DIR="/var/lib/powerdns"
SKIP_FIREWALL=0

# Colors for terminal output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

usage() {
    local exit_code="${1:-1}"
    cat <<EOF
Usage: sudo $0 --role <primary|secondary> [OPTIONS]

Required:
  -r, --role <primary|secondary>   Node role:
                                     - primary:   Master mode, REST API enabled (example-dns.net)
                                     - secondary: Slave mode, autosecondary enabled (example-dns.org)

Options:
  -k, --api-key <string>           PowerDNS HTTP API key for primary mode (default: auto-generated or \$PDNS_API_KEY)
  -d, --db-dir <path>              Directory for SQLite database (default: /var/lib/powerdns)
  --skip-firewall                  Do not configure UFW firewall rules
  -h, --help                       Show this help message
EOF
    exit "$exit_code"
}

# Parse Arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        -r|--role)
            ROLE="$2"
            shift 2
            ;;
        -k|--api-key)
            API_KEY="$2"
            shift 2
            ;;
        -d|--db-dir)
            DB_DIR="$2"
            shift 2
            ;;
        --skip-firewall)
            SKIP_FIREWALL=1
            shift
            ;;
        -h|--help)
            usage 0
            ;;
        *)
            log_error "Unknown argument: $1"
            usage
            ;;
    esac
done

# Validation
if [[ $EUID -ne 0 ]]; then
    log_error "This script must be run as root (use sudo)."
    exit 1
fi

if [[ -z "$ROLE" ]]; then
    log_error "Role must be specified (--role primary OR --role secondary)."
    usage
fi

if [[ "$ROLE" != "primary" && "$ROLE" != "secondary" ]]; then
    log_error "Invalid role: '$ROLE'. Must be 'primary' or 'secondary'."
    exit 1
fi

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS_ID="$ID"
    OS_VERSION_ID="$VERSION_ID"
else
    log_error "Cannot detect operating system (/etc/os-release missing)."
    exit 1
fi

if [[ "$OS_ID" != "ubuntu" && "$OS_ID" != "debian" ]]; then
    log_warn "This script is tailored for Ubuntu / Debian. Detected: $OS_ID ($VERSION_ID). Proceeding anyway..."
fi

log_info "Starting example-dns VPS installation for role: ${ROLE} on ${OS_ID} ${OS_VERSION_ID}"

# Step 1: Resolve systemd-resolved port 53 conflict if present
log_info "Step 1/5: Checking for port 53 conflicts (systemd-resolved)..."
if systemctl is-active --quiet systemd-resolved 2>/dev/null; then
    log_info "systemd-resolved detected. Disabling DNSStubListener so PowerDNS can bind to port 53..."
    mkdir -p /etc/systemd/resolved.conf.d
    cat > /etc/systemd/resolved.conf.d/example-dns-disable-stub.conf <<'RESOLVED_EOF'
[Resolve]
DNSStubListener=no
RESOLVED_EOF

    # Ensure /etc/resolv.conf uses an external upstream resolver or systemd-resolved uplink
    if [ -L /etc/resolv.conf ]; then
        RESOLV_TARGET=$(readlink -f /etc/resolv.conf || true)
        if [[ "$RESOLV_TARGET" == *"/run/systemd/resolve/stub-resolv.conf" ]]; then
            ln -sf /run/systemd/resolve/resolv.conf /etc/resolv.conf
            log_info "Re-pointed /etc/resolv.conf to /run/systemd/resolve/resolv.conf"
        fi
    fi

    systemctl restart systemd-resolved || log_warn "Could not restart systemd-resolved; continuing."
    log_success "systemd-resolved DNSStubListener disabled."
else
    log_info "systemd-resolved is not active. No port 53 conflict detected."
fi

# Step 2: Install PowerDNS and SQLite backend
log_info "Step 2/5: Installing PowerDNS Authoritative Server and SQLite3 backend..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y --no-install-recommends \
    pdns-server \
    pdns-backend-sqlite3 \
    sqlite3 \
    curl \
    ca-certificates

# Step 3: Initialize SQLite Database and Schema
log_info "Step 3/5: Setting up database storage in ${DB_DIR}..."
mkdir -p "${DB_DIR}"

DB_FILE="${DB_DIR}/pdns-${ROLE}.sqlite3"
SCHEMA_SOURCE=""

# Locate schema.sql
if [ -f "${SCRIPT_DIR}/../powerdns/schema.sql" ]; then
    SCHEMA_SOURCE="${SCRIPT_DIR}/../powerdns/schema.sql"
elif [ -f "/etc/powerdns/schema.sql" ]; then
    SCHEMA_SOURCE="/etc/powerdns/schema.sql"
fi

if [ ! -f "${DB_FILE}" ]; then
    log_info "Initializing database: ${DB_FILE}"
    touch "${DB_FILE}"
    if [ -n "${SCHEMA_SOURCE}" ] && [ -f "${SCHEMA_SOURCE}" ]; then
        log_info "Applying schema from ${SCHEMA_SOURCE}..."
        sqlite3 "${DB_FILE}" < "${SCHEMA_SOURCE}"
    else
        log_warn "schema.sql not found locally. Fetching standard PowerDNS SQLite schema..."
        curl -sSL "https://raw.githubusercontent.com/PowerDNS/pdns/master/modules/gsqlite3backend/schema.sqlite3.sql" | sqlite3 "${DB_FILE}"
    fi
    log_success "Database schema created."
else
    log_info "Existing database found at ${DB_FILE}; preserving."
fi

# Fix permissions
chown -R pdns:pdns "${DB_DIR}"
chmod 750 "${DB_DIR}"
chmod 640 "${DB_FILE}"
chown pdns:pdns "${DB_FILE}"

# Step 4: Write PowerDNS Configuration
log_info "Step 4/5: Writing PowerDNS configuration to /etc/powerdns/pdns.conf..."

# Backup existing configuration if present
if [ -f /etc/powerdns/pdns.conf ] && [ ! -f /etc/powerdns/pdns.conf.orig ]; then
    cp /etc/powerdns/pdns.conf /etc/powerdns/pdns.conf.orig
    log_info "Backed up original config to /etc/powerdns/pdns.conf.orig"
fi

# Remove packaged default conf-includes or conflicting debian snippets if present
rm -f /etc/powerdns/pdns.d/bind.conf || true

if [[ "$ROLE" == "primary" ]]; then
    if [ -z "$API_KEY" ]; then
        API_KEY=$(head -c 24 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
        log_info "Generated random PowerDNS REST API key."
    fi

    cat > /etc/powerdns/pdns.conf <<EOF
# PowerDNS Authoritative Server - Primary Nameserver (example-dns.net)
# Generated by example-dns VPS installer

# Network & Launch
launch=gsqlite3
local-address=0.0.0.0, ::
local-port=53
distributor-threads=2
receiver-threads=2

# Primary / Master Mode
master=yes
slave=no

# Backend Configuration (SQLite3)
gsqlite3-database=${DB_FILE}
gsqlite3-pragma-synchronous=1
gsqlite3-pragma-foreign-keys=1

# Built-in HTTP REST API & Webserver (for example-dns.com web dashboard)
webserver=yes
webserver-address=0.0.0.0
webserver-port=8081
webserver-allow-from=127.0.0.1, ::1, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
api=yes
api-key=${API_KEY}

# Logging
loglevel=4
log-dns-queries=no
log-timestamp=yes

# DNSSEC
dnssec=yes
EOF

else
    cat > /etc/powerdns/pdns.conf <<EOF
# PowerDNS Authoritative Server - Secondary Nameserver (example-dns.org)
# Generated by example-dns VPS installer

# Network & Launch
launch=gsqlite3
local-address=0.0.0.0, ::
local-port=53
distributor-threads=2
receiver-threads=2

# Secondary / Slave Mode
master=no
slave=yes
slave-cycle-interval=60

# Supermaster / Automatic zone provisioning from primary
autosecondary=yes

# Backend Configuration (SQLite3)
gsqlite3-database=${DB_FILE}
gsqlite3-pragma-synchronous=1
gsqlite3-pragma-foreign-keys=1

# Logging
loglevel=4
log-dns-queries=no
log-timestamp=yes

# DNSSEC
dnssec=yes
EOF
fi

chmod 640 /etc/powerdns/pdns.conf
chown root:pdns /etc/powerdns/pdns.conf

# Step 5: Configure Firewall & Start Services
log_info "Step 5/5: Configuring firewall and enabling pdns service..."

if [[ $SKIP_FIREWALL -eq 0 ]] && command -v ufw >/dev/null 2>&1; then
    if ufw status | grep -qw "active"; then
        log_info "Configuring UFW rules..."
        ufw allow 53/udp comment "PowerDNS Authoritative DNS UDP"
        ufw allow 53/tcp comment "PowerDNS Authoritative DNS TCP"
        if [[ "$ROLE" == "primary" ]]; then
            log_warn "API port 8081 should only be exposed to trusted IPs or web app servers."
        fi
        log_success "UFW rules applied."
    fi
fi

systemctl daemon-reload
systemctl enable pdns
systemctl restart pdns

sleep 1

# Check service status
if systemctl is-active --quiet pdns; then
    log_success "PowerDNS service (pdns) is ACTIVE and RUNNING!"
else
    log_error "PowerDNS service failed to start. Inspect logs with: journalctl -u pdns -n 50"
    exit 1
fi

echo ""
echo "======================================================================"
echo -e "${GREEN}example-dns VPS Installation Complete!${NC}"
echo "======================================================================"
echo -e "Node Role:       ${BLUE}${ROLE}${NC}"
echo -e "Database Path:   ${DB_FILE}"
echo -e "Config File:     /etc/powerdns/pdns.conf"
if [[ "$ROLE" == "primary" ]]; then
    echo -e "REST API Port:   8081"
    echo -e "REST API Key:    ${YELLOW}${API_KEY}${NC}"
    echo -e "API Endpoint:    http://127.0.0.1:8081/api/v1/servers/localhost"
fi
echo ""
echo "Helpful verification commands:"
echo "  pdns_control ping"
echo "  dig @127.0.0.1 -p 53 example-dns.com ANY"
echo "  systemctl status pdns"
echo "  journalctl -u pdns -f"
echo "======================================================================"
