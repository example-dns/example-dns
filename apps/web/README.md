# example-dns Web Interface (`example-dns.com`)

This directory houses the PHP-powered web landing page and portal for `example-dns.com`.

## Features

- **Zero-Dependency Architecture**: Lightweight server-side PHP with pure semantic HTML, zero CSS frameworks, zero JS build pipelines, and zero runtime dependencies.
- **Full Internationalization (i18n)**: English (`en`) and German (`de`) with 1:1 key parity, automatic browser-language detection, and manual switching (`?locale=en`, `?locale=de`).
- **Live Cluster Topology & Node Specifications**: Real-time display of Node 1 (Primary Master, PowerDNS 4.9, MariaDB) and Node 2 (Secondary Replica, PowerDNS 4.7, SQLite3) with one-click copyable IPv4/IPv6 addresses and autonomous system numbers (AS210083 & AS207198).
- **Interactive Delegation Guides**: Tabbed switcher for standard dual-nameserver setup (recommended for strict registries like DENIC `.de`) and redundant quad-nameserver configuration.
- **Terminal DNS Diagnostics**: Copyable `dig` command snippets to verify live SOA, NS delegation, and DNSSEC keys directly from any local terminal.
- **Architectural & Deployment Showcase**: Highlights multi-subnet AS diversity, AXFR supermaster replication, native DNSSEC, Caddy on-demand TLS validation, and quickstart commands for Docker Compose and zero-touch VPS installation.
- **Modern Minimalist Aesthetics**: Crisp typography, high-contrast monochrome design with subtle emerald status accents, animated operational pulse indicator, and instant clipboard feedback.
- **SEO & Structured Data**: Canonical links, `hreflang` tags, Open Graph, Twitter Cards, and Schema.org JSON-LD graph.
- **Legal Compliance**: Direct imprint link to `https://ternis.dev/{locale}/legal/imprint`.

## Local Development

Run using PHP's built-in web server:

```bash
# From repository root
php -S localhost:8080 -t apps/web
```

Then visit `http://localhost:8080` in your browser.

## Verification

```bash
# Check PHP syntax
php -l apps/web/index.php
php -l apps/web/locales/en.php
php -l apps/web/locales/de.php
```
