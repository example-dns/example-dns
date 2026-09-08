# example-dns

`example-dns` is the official monorepo for the open-source DNS network and service infrastructure created by [ternis.org](https://ternis.org) (by [ternis.dev](https://ternis.dev) and [ternis.net](https://ternis.net)). The nameserver network is powered by **PowerDNS Authoritative Server**.

---

## Repositories & Mirrors

This project is mirrored across multiple platforms:

- **GitHub**: [https://github.com/example-dns/example-dns](https://github.com/example-dns/example-dns)
- **Codeberg**: [https://codeberg.org/example-dns/example-dns](https://codeberg.org/example-dns/example-dns)

See [Repository Mirrors](docs/mirrors.md) for local git configuration.

---

## Infrastructure & Domains

| Domain | Role / Service | Description | Path |
| --- | --- | --- | --- |
| **`example-dns.com`** | Web Interface & Landing Page | Management dashboard, public landing page, and API | [`apps/web`](apps/web) |
| **`example-dns.net`** | Primary Nameserver | Authoritative PowerDNS master nameserver & REST API | [`infra/powerdns/primary.conf`](infra/powerdns/primary.conf) |
| **`example-dns.org`** | Secondary Nameserver | Authoritative PowerDNS slave / fallback nameserver | [`infra/powerdns/secondary.conf`](infra/powerdns/secondary.conf) |

---

## Monorepo Layout

```
.
├── apps/
│   └── web/                     # example-dns.com PHP landing page & web app
├── infra/                       # PowerDNS configs, schema, Docker Compose, and deployment manifests
├── docs/                        # Architecture and ecosystem documentation
├── CONTRIBUTING.md              # Contribution guidelines
├── SECURITY.md                  # Security reporting policy
├── LICENSE                      # MIT License
└── README.md
```

Detailed architectural specifications and data flow diagrams are available in [Architecture Documentation](docs/architecture.md). Deployment and PowerDNS setup instructions are available in [Infrastructure README](infra/README.md).

---

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

---

## License

The entire `example-dns` source code is open-source and free to use. Commercial use requires crediting this project.

Licensed under the [MIT License](LICENSE).
