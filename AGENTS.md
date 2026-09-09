# AGENTS.md — AI Agent Guide for example-dns

Welcome, AI Agent! This document provides the essential architectural context, codebase layout, operational constraints, and conventions for working on the `example-dns` project.

---

## 1. Project Mission & Identity

- **Project**: `example-dns`
- **Owner / Creator**: [ternis.org](https://ternis.org) (by [ternis.dev](https://ternis.dev) and [ternis.net](https://ternis.net))
- **License**: MIT License (Commercial use requires crediting this project)
- **Goal**: Provide open-source, resilient, high-performance authoritative DNS network infrastructure and web services powered by PowerDNS Authoritative Server.

### Repository Mirrors & Dual-Push Requirement
The canonical repository is mirrored across two platforms:
1. **GitHub**: `https://github.com/example-dns/example-dns` (remote name: `github`)
2. **Codeberg**: `https://codeberg.org/example-dns/example-dns` (remote name: `codeberg`)

> [!IMPORTANT]
> When executing git push operations, **always push to both remotes**:
> ```bash
> git push github master && git push codeberg master
> ```

---

## 2. Infrastructure & Domain Roles

The ecosystem is partitioned across three core domain names:

| Domain | Role | Implementation | Location |
| --- | --- | --- | --- |
| **`example-dns.com`** | Public Web Portal & Landing Page | Lightweight server-side rendered PHP app (semantic HTML, i18n) | [`apps/web`](apps/web) |
| **`example-dns.net`** | Primary Authoritative Nameserver | PowerDNS Authoritative Server (master mode, REST API, SQLite backend) | [`infra/powerdns/primary.conf`](infra/powerdns/primary.conf) |
| **`example-dns.org`** | Secondary Authoritative Nameserver | PowerDNS Authoritative Server (slave mode, `autosecondary`, AXFR replication) | [`infra/powerdns/secondary.conf`](infra/powerdns/secondary.conf) |

---

## 3. Monorepo Structure

```
.
├── AGENTS.md                    # Agent guidelines and architectural specification
├── CONTRIBUTING.md              # Human contributor guidelines
├── LICENSE                      # MIT License
├── README.md                    # Public project overview
├── SECURITY.md                  # Security reporting policy
├── apps/
│   └── web/                     # example-dns.com PHP web application
│       ├── index.php            # Main entrypoint & HTML rendering
│       ├── locales/             # i18n dictionary translations
│       │   ├── de.php           # German translations
│       │   └── en.php           # English translations
│       └── README.md            # Web app documentation
├── docs/
│   ├── architecture.md          # Architectural specifications & Mermaid flowcharts
│   └── mirrors.md               # Git mirror setup instructions
└── infra/
    ├── docker-compose.yml       # Complete local/containerized stack with auto-initialized SQLite
    ├── README.md                # Deployment guide (Docker & VPS)
    ├── powerdns/
    │   ├── primary.conf         # Master configuration & HTTP API (:8081)
    │   ├── secondary.conf       # Slave configuration with autosecondary
    │   └── schema.sql           # SQLite3 database schema for PowerDNS
    └── scripts/
        └── install-vps.sh       # Zero-touch installer script for Debian/Ubuntu VPS nodes
```

---

## 4. Deployment Targets & Automation

### Target A: Containerized (Docker Compose)
- Located at [`infra/docker-compose.yml`](infra/docker-compose.yml).
- Includes an automated `init-db` Alpine service that initializes `/var/lib/powerdns/pdns-primary.sqlite3` and `pdns-secondary.sqlite3` from `schema.sql` before starting nameserver containers.
- Ports:
  - `powerdns-primary`: `53:53/udp`, `53:53/tcp`, `8081:8081` (REST API)
  - `powerdns-secondary`: `5353:53/udp`, `5353:53/tcp`
  - `web`: `8080:8080`

### Target B: Standalone VPS / Bare-Metal Linux Host
- Script: [`infra/scripts/install-vps.sh`](infra/scripts/install-vps.sh).
- Supports clean **Ubuntu** and **Debian** VPS nodes.
- Solves `systemd-resolved` port 53 collision (`DNSStubListener=no`) while safeguarding outbound DNS.
- Contains an offline embedded fallback schema to support piping directly from `curl`:
  ```bash
  curl -sSL https://raw.githubusercontent.com/example-dns/example-dns/master/infra/scripts/install-vps.sh | sudo bash -s -- --role <primary|secondary|web|all>
  ```
- Node roles:
  - `--role primary`: Configures master mode with REST API on port `8081`
  - `--role secondary`: Configures slave mode with `autosecondary=yes`
  - `--role web`: Installs PHP runtime, deploys web app, starts systemd service `example-dns-web.service`
  - `--role all`: Deploys primary nameserver and web app together on a single VPS

---

## 5. Coding & Development Standards

### Web Application (`apps/web`)
- **Language**: Modern PHP (`declare(strict_types=1);`).
- **Philosophy**: Pure semantic HTML, minimal footprint, **no CSS frameworks** and **no JavaScript build pipelines** (no npm, webpack, vite, or node_modules).
- **Internationalization**: Use associative arrays in `apps/web/locales/*.php`. Always maintain parity between `en.php` and `de.php`.
- **Imprint / Legal**: Must link to `https://ternis.dev/{locale}/legal/imprint`.

### PowerDNS Infrastructure (`infra/`)
- **Backend**: Generic SQLite3 (`gsqlite3`).
- **Permissions**: Database files must be owned by `pdns:pdns` with directory mode `750` and database file mode `640`.
- **DNSSEC**: Enabled across all nameserver configurations (`dnssec=yes`).
- **API Security**: The PowerDNS REST API (`webserver-port=8081`) must be protected by an API key (`api-key`) and restricted to private subnets/loopback.

### Git & Commit Conventions
- Use **Conventional Commits**:
  - `feat(scope): ...`
  - `fix(scope): ...`
  - `refactor(scope): ...`
  - `docs(scope): ...`
- Scope examples: `infra`, `web`, `docs`.

---

## 6. Verification & Health-Check Commands

When testing changes locally or advising users, use these commands:

```bash
# 1. Lint PHP files
php -l apps/web/index.php
php -l apps/web/locales/en.php
php -l apps/web/locales/de.php

# 2. Syntax-check bash installer
bash -n infra/scripts/install-vps.sh
bash infra/scripts/install-vps.sh --help

# 3. Test PowerDNS SQLite schema integrity
sqlite3 /tmp/test.sqlite3 < infra/powerdns/schema.sql && rm -f /tmp/test.sqlite3

# 4. Start Docker stack (from infra/)
cd infra && docker compose up -d

# 5. Query DNS records
dig @127.0.0.1 -p 53 example-dns.com ANY
dig @127.0.0.1 -p 5353 example-dns.com ANY

# 6. Verify PowerDNS daemon on Linux VPS
pdns_control ping
systemctl status pdns
```
