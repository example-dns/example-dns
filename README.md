# example-dns

`example-dns` is the official monorepo for the open-source DNS network and service infrastructure created by [ternis.org](https://ternis.org) (by [ternis.dev](https://ternis.dev) and [ternis.net](https://ternis.net)).

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
| **`example-dns.com`** | Web Interface & API | Management dashboard and public API | [`apps/web`](apps/web) |
| **`example-dns.net`** | Primary Nameserver | Authoritative master nameserver | [`apps/nameserver-primary`](apps/nameserver-primary) |
| **`example-dns.org`** | Secondary Nameserver | Authoritative slave / fallback nameserver | [`apps/nameserver-secondary`](apps/nameserver-secondary) |

---

## Monorepo Layout

```
.
├── apps/
│   ├── web/                     # example-dns.com web application & dashboard
│   ├── nameserver-primary/      # example-dns.net authoritative primary nameserver
│   └── nameserver-secondary/    # example-dns.org authoritative secondary nameserver
├── packages/
│   └── common/                  # Shared types, protocols, and config
├── infra/                       # Infrastructure, Docker, and deployment manifests
├── docs/                        # Architecture and ecosystem documentation
├── CONTRIBUTING.md              # Contribution guidelines
├── SECURITY.md                  # Security reporting policy
├── LICENSE                      # MIT License
└── README.md
```

Detailed architectural specifications and data flow diagrams are available in [Architecture Documentation](docs/architecture.md).

---

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

---

## License

The entire `example-dns` source code is open-source and free to use. Commercial use requires crediting this project.

Licensed under the [MIT License](LICENSE).
