#!/usr/bin/env bash
# ==============================================================================
# example-dns VPS Installer
# PowerDNS Authoritative Server Bare-Metal / VPS Provisioning Script
#
# Supports Debian and Ubuntu LTS distributions.
# Provisions Primary (example-dns.net), Secondary (example-dns.org), Web (example-dns.com), or All.
#
# Usage:
#   sudo bash install-vps.sh --role primary [--api-key <secret>] [--db-dir <dir>]
#   sudo bash install-vps.sh --role secondary [--db-dir <dir>]
#   sudo bash install-vps.sh --role web [--web-port 8080]
#   sudo bash install-vps.sh --role all [--api-key <secret>]
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" 2>/dev/null && pwd || echo "")"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." 2>/dev/null && pwd || echo "")"

# Defaults
ROLE=""
API_KEY="${PDNS_API_KEY:-}"
DB_DIR="/var/lib/powerdns"
WEB_PORT=8080
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
Usage: sudo $0 --role <primary|secondary|web|all> [OPTIONS]

Required:
  -r, --role <primary|secondary|web|all>
                               Node role:
                                 - primary:   PowerDNS master mode with REST API (example-dns.net)
                                 - secondary: PowerDNS slave mode with autosecondary (example-dns.org)
                                 - web:       example-dns.com web application & landing page
                                 - all:       Full stack on single VPS (primary + web)

Options:
  -k, --api-key <string>       PowerDNS HTTP API key for primary mode (default: auto-generated or \$PDNS_API_KEY)
  -d, --db-dir <path>          Directory for SQLite database (default: /var/lib/powerdns)
  -w, --web-port <port>        HTTP port for web service (default: 8080)
  --skip-firewall              Do not configure UFW firewall rules
  -h, --help                   Show this help message
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
        -w|--web-port)
            WEB_PORT="$2"
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
            usage 1
            ;;
    esac
done

# Validation
if [[ $EUID -ne 0 ]]; then
    log_error "This script must be run as root (use sudo)."
    exit 1
fi

if [[ -z "$ROLE" ]]; then
    log_error "Role must be specified (--role primary|secondary|web|all)."
    usage 1
fi

case "$ROLE" in
    primary|secondary|web|all) ;;
    *)
        log_error "Invalid role: '$ROLE'. Must be 'primary', 'secondary', 'web', or 'all'."
        exit 1
        ;;
esac

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS_ID="${ID:-unknown}"
    OS_VERSION_ID="${VERSION_ID:-unknown}"
else
    log_error "Cannot detect operating system (/etc/os-release missing)."
    exit 1
fi

if [[ "$OS_ID" != "ubuntu" && "$OS_ID" != "debian" ]]; then
    log_warn "This script is tested on Ubuntu and Debian. Detected: $OS_ID ($OS_VERSION_ID). Proceeding anyway..."
fi

log_info "Starting example-dns VPS installation for role: ${ROLE} on ${OS_ID} ${OS_VERSION_ID}"

# Function: Disable systemd-resolved stub listener while maintaining outbound DNS resolution
setup_resolved_and_dns() {
    log_info "Configuring host resolver and clearing port 53 conflicts..."
    if systemctl is-active --quiet systemd-resolved 2>/dev/null; then
        log_info "systemd-resolved is active. Configuring DNSStubListener=no..."
        mkdir -p /etc/systemd/resolved.conf.d
        cat > /etc/systemd/resolved.conf.d/example-dns-disable-stub.conf <<'RESOLVED_EOF'
[Resolve]
DNSStubListener=no
RESOLVED_EOF

        # Ensure /etc/resolv.conf uses uplink or fallback external nameservers
        if [ -L /etc/resolv.conf ]; then
            RESOLV_TARGET=$(readlink -f /etc/resolv.conf || true)
            if [[ "$RESOLV_TARGET" == *"/run/systemd/resolve/stub-resolv.conf" ]]; then
                ln -sf /run/systemd/resolve/resolv.conf /etc/resolv.conf
                log_info "Pointed /etc/resolv.conf to /run/systemd/resolve/resolv.conf"
            fi
        fi

        systemctl restart systemd-resolved || log_warn "Could not restart systemd-resolved; continuing."

        # Verify /etc/resolv.conf has working nameservers
        if ! grep -q "nameserver" /etc/resolv.conf 2>/dev/null || grep -q "127.0.0.53" /etc/resolv.conf 2>/dev/null; then
            log_info "Adding upstream fallback nameservers (1.1.1.1, 8.8.8.8) to /etc/resolv.conf..."
            sed -i '/127.0.0.53/d' /etc/resolv.conf 2>/dev/null || true
            echo "nameserver 1.1.1.1" >> /etc/resolv.conf
            echo "nameserver 8.8.8.8" >> /etc/resolv.conf
        fi
        log_success "Port 53 unblocked from systemd-resolved stub listener."
    fi
}

# Function: Apply SQLite Schema
apply_sqlite_schema() {
    local target_db="$1"
    if [ -n "${SCRIPT_DIR}" ] && [ -f "${SCRIPT_DIR}/../powerdns/schema.sql" ]; then
        log_info "Applying schema from local repo file: ${SCRIPT_DIR}/../powerdns/schema.sql"
        sqlite3 "${target_db}" < "${SCRIPT_DIR}/../powerdns/schema.sql"
    elif [ -f "/etc/powerdns/schema.sql" ]; then
        log_info "Applying schema from /etc/powerdns/schema.sql"
        sqlite3 "${target_db}" < "/etc/powerdns/schema.sql"
    else
        log_info "Applying embedded PowerDNS SQLite schema..."
        sqlite3 "${target_db}" << 'SCHEMA_EOF'
CREATE TABLE IF NOT EXISTS domains (
  id                    INTEGER PRIMARY KEY,
  name                  VARCHAR(255) NOT NULL COLLATE NOCASE,
  master                VARCHAR(128) DEFAULT NULL,
  last_check            INTEGER DEFAULT NULL,
  type                  VARCHAR(8) NOT NULL,
  notified_serial       INTEGER DEFAULT NULL,
  account               VARCHAR(40) DEFAULT NULL,
  options               VARCHAR(65535) DEFAULT NULL,
  catalog               VARCHAR(255) DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS name_index ON domains(name);
CREATE INDEX IF NOT EXISTS catalog_idx ON domains(catalog);

CREATE TABLE IF NOT EXISTS records (
  id                    INTEGER PRIMARY KEY,
  domain_id             INTEGER DEFAULT NULL,
  name                  VARCHAR(255) DEFAULT NULL,
  type                  VARCHAR(10) DEFAULT NULL,
  content               VARCHAR(65535) DEFAULT NULL,
  ttl                   INTEGER DEFAULT NULL,
  prio                  INTEGER DEFAULT NULL,
  disabled              BOOLEAN DEFAULT 0,
  ordername             VARCHAR(255),
  auth                  BOOLEAN DEFAULT 1,
  FOREIGN KEY(domain_id) REFERENCES domains(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS rec_name_index ON records(name);
CREATE INDEX IF NOT EXISTS nametype_index ON records(name,type);
CREATE INDEX IF NOT EXISTS orderindex ON records(ordername);

CREATE TABLE IF NOT EXISTS supermasters (
  ip                    VARCHAR(64) NOT NULL,
  nameserver            VARCHAR(255) NOT NULL COLLATE NOCASE,
  account               VARCHAR(40) NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS ip_nameserver_key ON supermasters(ip, nameserver);

CREATE TABLE IF NOT EXISTS comments (
  id                    INTEGER PRIMARY KEY,
  domain_id             INTEGER NOT NULL,
  name                  VARCHAR(255) NOT NULL,
  type                  VARCHAR(10) NOT NULL,
  modified_at           INTEGER NOT NULL,
  account               VARCHAR(40) DEFAULT NULL,
  comment               VARCHAR(65535) NOT NULL,
  FOREIGN KEY(domain_id) REFERENCES domains(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS comments_name_type_idx ON comments(name, type);
CREATE INDEX IF NOT EXISTS comments_order_idx ON comments(domain_id, modified_at);

CREATE TABLE IF NOT EXISTS domainmetadata (
  id                    INTEGER PRIMARY KEY,
  domain_id             INTEGER NOT NULL,
  kind                  VARCHAR(32) COLLATE NOCASE,
  content               TEXT,
  FOREIGN KEY(domain_id) REFERENCES domains(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS domainmetadata_idx ON domainmetadata(domain_id, kind);

CREATE TABLE IF NOT EXISTS cryptokeys (
  id                    INTEGER PRIMARY KEY,
  domain_id             INTEGER NOT NULL,
  flags                 INTEGER NOT NULL,
  active                BOOLEAN,
  published             BOOLEAN DEFAULT 1,
  content               TEXT,
  FOREIGN KEY(domain_id) REFERENCES domains(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS domainidindex ON cryptokeys(domain_id);

CREATE TABLE IF NOT EXISTS tsigkeys (
  id                    INTEGER PRIMARY KEY,
  name                  VARCHAR(255) COLLATE NOCASE,
  algorithm             VARCHAR(50) COLLATE NOCASE,
  secret                VARCHAR(255)
);
CREATE UNIQUE INDEX IF NOT EXISTS namealgoindex ON tsigkeys(name, algorithm);
SCHEMA_EOF
    fi
}

# Function: Install PowerDNS Nameserver
install_powerdns() {
    local pdns_mode="$1" # primary or secondary

    setup_resolved_and_dns

    log_info "Installing PowerDNS Authoritative Server and SQLite3 backend packages..."
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -y
    apt-get install -y --no-install-recommends \
        pdns-server \
        pdns-backend-sqlite3 \
        sqlite3 \
        curl \
        ca-certificates

    # Clean up default Debian snippet includes that could conflict with gsqlite3 launch
    if [ -d /etc/powerdns/pdns.d ]; then
        mkdir -p /etc/powerdns/pdns.d.bak
        mv -f /etc/powerdns/pdns.d/*.conf /etc/powerdns/pdns.d.bak/ 2>/dev/null || true
    fi

    # Database directory & file setup
    mkdir -p "${DB_DIR}"
    local db_file="${DB_DIR}/pdns-${pdns_mode}.sqlite3"

    if [ ! -s "${db_file}" ]; then
        log_info "Creating and initializing SQLite database at ${db_file}..."
        touch "${db_file}"
        apply_sqlite_schema "${db_file}"
        log_success "Database schema initialized."
    else
        log_info "Existing database found at ${db_file}; preserving."
    fi

    chown -R pdns:pdns "${DB_DIR}"
    chmod 750 "${DB_DIR}"
    chmod 640 "${db_file}"
    chown pdns:pdns "${db_file}"

    # Determine listen address (check if IPv6 is available)
    local listen_addr="0.0.0.0"
    if [ -f /proc/net/if_inet6 ]; then
        listen_addr="0.0.0.0, ::"
    fi

    # Backup existing configuration
    if [ -f /etc/powerdns/pdns.conf ] && [ ! -f /etc/powerdns/pdns.conf.orig ]; then
        cp /etc/powerdns/pdns.conf /etc/powerdns/pdns.conf.orig
    fi

    if [[ "$pdns_mode" == "primary" ]]; then
        if [ -z "$API_KEY" ]; then
            API_KEY=$(head -c 24 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
            log_info "Generated random PowerDNS REST API key."
        fi

        cat > /etc/powerdns/pdns.conf <<EOF
# PowerDNS Authoritative Server - Primary Nameserver (example-dns.net)
# Generated by example-dns VPS installer

# Network & Launch
launch=gsqlite3
local-address=${listen_addr}
local-port=53
distributor-threads=2
receiver-threads=2

# Master Mode
master=yes
slave=no

# Backend Configuration (SQLite3)
gsqlite3-database=${db_file}
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
local-address=${listen_addr}
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
gsqlite3-database=${db_file}
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

    # Configure UFW
    if [[ $SKIP_FIREWALL -eq 0 ]] && command -v ufw >/dev/null 2>&1; then
        if ufw status | grep -qw "active"; then
            log_info "Configuring UFW rules for DNS..."
            ufw allow 53/udp comment "PowerDNS UDP"
            ufw allow 53/tcp comment "PowerDNS TCP"
            log_success "UFW rules applied."
        fi
    fi

    systemctl daemon-reload
    systemctl enable pdns
    systemctl restart pdns
    sleep 1

    if systemctl is-active --quiet pdns; then
        log_success "PowerDNS service (pdns) is ACTIVE and RUNNING!"
    else
        log_error "PowerDNS service failed to start. Check logs: journalctl -u pdns -n 50"
        exit 1
    fi
}

# Function: Install Web Application
install_web() {
    log_info "Setting up example-dns Web Application (example-dns.com)..."
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -y
    apt-get install -y --no-install-recommends php-cli php-curl php-json curl ca-certificates

    mkdir -p /var/www/example-dns/apps/web/locales

    if [ -n "${REPO_ROOT}" ] && [ -d "${REPO_ROOT}/apps/web" ]; then
        log_info "Copying web app from local repository..."
        cp -r "${REPO_ROOT}/apps/web/"* /var/www/example-dns/apps/web/
    else
        log_info "Fetching web application files from repository mirror..."
        curl -sSL "https://raw.githubusercontent.com/example-dns/example-dns/master/apps/web/index.php" \
            -o /var/www/example-dns/apps/web/index.php
        curl -sSL "https://raw.githubusercontent.com/example-dns/example-dns/master/apps/web/locales/en.php" \
            -o /var/www/example-dns/apps/web/locales/en.php
        curl -sSL "https://raw.githubusercontent.com/example-dns/example-dns/master/apps/web/locales/de.php" \
            -o /var/www/example-dns/apps/web/locales/de.php
    fi

    # Create systemd service unit for PHP web application
    cat > /etc/systemd/system/example-dns-web.service <<EOF
[Unit]
Description=example-dns Web Application (example-dns.com)
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/example-dns/apps/web
ExecStart=/usr/bin/php -S 0.0.0.0:${WEB_PORT} -t /var/www/example-dns/apps/web
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

    chown -R www-data:www-data /var/www/example-dns
    systemctl daemon-reload
    systemctl enable example-dns-web
    systemctl restart example-dns-web
    sleep 1

    if systemctl is-active --quiet example-dns-web; then
        log_success "example-dns Web Service is ACTIVE on port ${WEB_PORT}!"
    else
        log_error "example-dns Web Service failed to start. Check logs: journalctl -u example-dns-web -n 50"
        exit 1
    fi

    if [[ $SKIP_FIREWALL -eq 0 ]] && command -v ufw >/dev/null 2>&1; then
        if ufw status | grep -qw "active"; then
            ufw allow "${WEB_PORT}/tcp" comment "example-dns Web Port"
        fi
    fi
}

# Execution based on selected Role
case "$ROLE" in
    primary)
        install_powerdns "primary"
        ;;
    secondary)
        install_powerdns "secondary"
        ;;
    web)
        install_web
        ;;
    all)
        install_powerdns "primary"
        install_web
        ;;
esac

echo ""
echo "======================================================================"
echo -e "${GREEN}example-dns VPS Installation Complete!${NC}"
echo "======================================================================"
echo -e "Node Role:       ${BLUE}${ROLE}${NC}"
if [[ "$ROLE" == "primary" || "$ROLE" == "all" ]]; then
    echo -e "Database:        ${DB_DIR}/pdns-primary.sqlite3"
    echo -e "REST API Port:   8081"
    echo -e "REST API Key:    ${YELLOW}${API_KEY}${NC}"
    echo -e "API Endpoint:    http://127.0.0.1:8081/api/v1/servers/localhost"
fi
if [[ "$ROLE" == "secondary" ]]; then
    echo -e "Database:        ${DB_DIR}/pdns-secondary.sqlite3"
fi
if [[ "$ROLE" == "web" || "$ROLE" == "all" ]]; then
    echo -e "Web URL:         http://0.0.0.0:${WEB_PORT}"
fi
echo ""
echo "Verification commands:"
if [[ "$ROLE" == "primary" || "$ROLE" == "secondary" || "$ROLE" == "all" ]]; then
    echo "  pdns_control ping"
    echo "  dig @127.0.0.1 -p 53 example-dns.com ANY"
    echo "  systemctl status pdns"
fi
if [[ "$ROLE" == "web" || "$ROLE" == "all" ]]; then
    echo "  curl -I http://127.0.0.1:${WEB_PORT}"
    echo "  systemctl status example-dns-web"
fi
echo "======================================================================"
