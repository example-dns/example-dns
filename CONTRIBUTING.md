# Contributing to example-dns

Thank you for your interest in contributing to `example-dns`!

`example-dns` is an open-source project by [ternis.org](https://ternis.org) (by [ternis.dev](https://ternis.dev) and [ternis.net](https://ternis.net)).

## Getting Started

1. **Repository Mirrors**:
   - GitHub: [https://github.com/example-dns/example-dns](https://github.com/example-dns/example-dns)
   - Codeberg: [https://codeberg.org/example-dns/example-dns](https://codeberg.org/example-dns/example-dns)
2. Review the [Architecture Documentation](docs/architecture.md) to understand how the applications and nameservers interact.

## Monorepo Workflow

- Changes to the web UI and management API should be scoped within `apps/web/`.
- Changes to the primary nameserver belong in `apps/nameserver-primary/`.
- Changes to the secondary nameserver belong in `apps/nameserver-secondary/`.
- Shared logic, models, and protocols belong in `packages/common/`.
- Infrastructure and deployment configurations belong in `infra/`.

## Pull Request Guidelines

- Ensure your commit messages are clear and follow conventional commit formats (`feat:`, `fix:`, `docs:`, `chore:`, etc.).
- Update documentation in `docs/` whenever architectural changes or new configuration options are introduced.

## Attribution & License

All contributions will be licensed under the [MIT License](LICENSE). Note that commercial use of this project requires attribution to `ternis.org`.
