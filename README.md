# example-dns

`example-dns` is the official monorepo for the **100% open-source** authoritative DNS network and service infrastructure created by [ternis.org](https://ternis.org) (by [ternis.dev](https://ternis.dev) and [ternis.net](https://ternis.net)), authored and maintained by **Fabian Ternis** (`f.ternis@xpsystems.eu`). The nameserver network is powered by **PowerDNS Authoritative Server**.

---

## Repositories & Mirrors

This project is mirrored across multiple open-source platforms:

- **GitHub**: [https://github.com/example-dns/example-dns](https://github.com/example-dns/example-dns)
- **Codeberg**: [https://codeberg.org/example-dns/example-dns](https://codeberg.org/example-dns/example-dns)

See [Repository Mirrors](docs/mirrors.md) for local git configuration.

---

## Infrastructure & Domains

The `example-dns` network operates across three dedicated core domains:

| Domain | Role / Service | Description | Path |
| --- | --- | --- | --- |
| **`example-dns.com`** | Web Interface & Landing Page | Public portal, web interface, and PHP landing page | [`apps/web`](apps/web) |
| **`example-dns.net`** | Primary Nameserver | Authoritative PowerDNS master nameserver & REST API | [`infra/powerdns/primary.conf`](infra/powerdns/primary.conf) |
| **`example-dns.org`** | Secondary Nameserver | Authoritative PowerDNS slave / fallback nameserver | [`infra/powerdns/secondary.conf`](infra/powerdns/secondary.conf) |

---

## Live Production Environment

The production deployment runs in a high-availability **Dual-Cluster Topology** operating four authoritative nameservers in parallel across diverse subnets:

- **Node 1 (Primary - `77.90.60.110`)**: `example-dns.net` & `one.ns.ternis.net` (PowerDNS 4.9, MariaDB backend, Caddy HTTPS, PHP 8.5)
- **Node 2 (Secondary - `94.249.188.145`)**: `example-dns.org` & `two.ns.ternis.net` (PowerDNS 4.7, SQLite3 backend, Caddy HTTPS, PHP 8.2)

Managed domains can declare all four nameservers (`example-dns.net`, `example-dns.org`, `one.ns.ternis.net`, `two.ns.ternis.net`) for maximum geographic and network redundancy.

Detailed production architecture, node specifications, zone templates, and failover workflows are documented in **[Production Environment & Deployment](docs/production-deployment.md)**.

---

## Monorepo Layout

```
.
├── apps/
│   └── web/                     # example-dns.com PHP landing page & web app
├── infra/                       # PowerDNS configs, schema, Docker Compose, and deployment manifests
│   ├── docker-compose.yml       # Local and containerized multi-node orchestration
│   ├── powerdns/                # Primary & Secondary PowerDNS configuration templates
│   └── scripts/                 # Bare-metal VPS zero-touch installer script
├── docs/                        # Architectural, deployment, and mirror documentation
│   ├── architecture.md          # System architecture and flowcharts
│   ├── production-deployment.md # Live production environment specifications
│   └── mirrors.md               # Git mirror push and fetch configuration
├── CONTRIBUTING.md              # Contribution guidelines
├── SECURITY.md                  # Security reporting policy
├── LICENSE                      # MIT License
└── README.md
```

---

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

---

## License

The entire `example-dns` source code and infrastructure configurations are **100% open-source** and free to use under the terms of the [MIT License](LICENSE).
