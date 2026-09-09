# Infrastructure & PowerDNS Deployment

This directory contains infrastructure-as-code, PowerDNS configuration, and Docker orchestration for the `example-dns` network.

## Architecture

The DNS network is powered by **PowerDNS Authoritative Server**:

- **`example-dns.net` (Primary Nameserver)**:
  - Runs in master mode (`master=yes`) using the `gsqlite3` backend.
  - Exposes the PowerDNS HTTP REST API (`/api/v1`) on port `8081` for automated record updates and management by `example-dns.com`.
  - Configured in [`powerdns/primary.conf`](powerdns/primary.conf).

- **`example-dns.org` (Secondary Nameserver)**:
  - Runs in slave mode (`slave=yes`) with automatic secondary zone replication (`autosecondary=yes`).
  - Automatically fetches updated zone data from the primary nameserver via AXFR/IXFR.
  - Configured in [`powerdns/secondary.conf`](powerdns/secondary.conf).

## Deployment Options

`example-dns` nodes can be deployed either via **Docker Compose** (for local development, containerized environments, and staging) or natively on a **dedicated VPS / Bare-Metal Linux host** (for production edge nameservers).

---

### Option 1: Native VPS / Bare-Metal Deployment

For production nameservers on a clean Debian or Ubuntu VPS (e.g., at Hetzner, DigitalOcean, Linode, OVH):

#### 1. Primary Nameserver (`example-dns.net`)

Clone the repo or transfer `infra/` to the VPS, then run:

```bash
sudo bash infra/scripts/install-vps.sh --role primary --api-key "your-secure-api-key"
```

The installer will:
1. Disable `systemd-resolved`'s local DNS stub listener to free port `53`.
2. Install `pdns-server` and `pdns-backend-sqlite3`.
3. Initialize the SQLite schema in `/var/lib/powerdns/pdns-primary.sqlite3`.
4. Configure `/etc/powerdns/pdns.conf` in master mode with the REST API enabled on port `8081`.
5. Configure UFW firewall rules for DNS traffic (UDP/TCP 53).
6. Enable and restart `pdns.service`.

#### 2. Secondary Nameserver (`example-dns.org`)

On your secondary VPS:

```bash
sudo bash infra/scripts/install-vps.sh --role secondary
```

The installer configures PowerDNS in slave mode with `autosecondary=yes`, listening on port 53.

#### 3. Authorizing Zone Transfers between VPS Nodes

To allow the secondary nameserver to automatically receive DNS NOTIFY packets and provision zones from the primary:

On the **secondary VPS**, add the primary VPS IP address to the `supermasters` table:

```bash
sqlite3 /var/lib/powerdns/pdns-secondary.sqlite3 \
  "INSERT INTO supermasters (ip, nameserver, account) VALUES ('<PRIMARY_VPS_IP>', 'example-dns.net', 'admin');"
```

On the **primary VPS**, ensure `/etc/powerdns/pdns.conf` permits AXFR transfers to the secondary:

```ini
allow-axfr-ips=<SECONDARY_VPS_IP>
only-notify=<SECONDARY_VPS_IP>
```

Then reload PowerDNS: `sudo systemctl reload pdns`.

---

### Option 2: Running the Stack with Docker Compose

To spin up both PowerDNS authoritative nameservers and the PHP web landing page locally or in container clusters:

```bash
docker compose up -d
```

#### Services & Port Mappings

| Service | Container | Ports | Role |
| --- | --- | --- | --- |
| `powerdns-primary` | `example-dns-primary` | `53:53` (UDP/TCP), `8081:8081` (API) | Primary Nameserver (`example-dns.net`) |
| `powerdns-secondary` | `example-dns-secondary` | `5353:53` (UDP/TCP) | Secondary Nameserver (`example-dns.org`) |
| `web` | `example-dns-web` | `8080:8080` | Web landing page (`example-dns.com`) |

---

## Querying and Verifying the DNS Servers

```bash
# Test querying primary nameserver
dig @127.0.0.1 -p 53 example-dns.com ANY

# Test querying secondary nameserver (port 5353 if Docker, port 53 if native VPS)
dig @127.0.0.1 -p 53 example-dns.com ANY

# Check service status on VPS
pdns_control ping
systemctl status pdns
```

