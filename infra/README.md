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

## Running the Stack with Docker Compose

To spin up both PowerDNS authoritative nameservers and the static HTML web landing page:

```bash
docker compose up -d
```

### Services & Port Mappings

| Service | Container | Ports | Role |
| --- | --- | --- | --- |
| `powerdns-primary` | `example-dns-primary` | `53:53` (UDP/TCP), `8081:8081` (API) | Primary Nameserver (`example-dns.net`) |
| `powerdns-secondary` | `example-dns-secondary` | `5353:53` (UDP/TCP) | Secondary Nameserver (`example-dns.org`) |
| `web` | `example-dns-web` | `8080:80` | Web landing page (`example-dns.com`) |

## Querying the DNS Servers

```bash
# Test querying primary nameserver
dig @127.0.0.1 -p 53 example-dns.com ANY

# Test querying secondary nameserver
dig @127.0.0.1 -p 5353 example-dns.com ANY
```
