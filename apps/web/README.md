# example-dns Web Interface (`example-dns.com`)

This directory houses the PHP-powered web landing page and portal for `example-dns.com`.

## Features

- **Zero-Dependency Architecture**: Pure server-side PHP with semantic HTML5, zero CSS frameworks, zero JS build pipelines, and zero runtime dependencies.
- **Full Internationalization (i18n)**: English (`en`) and German (`de`) with 1:1 key parity, automatic browser-language detection, and manual switching (`?locale=en`, `?locale=de`).
- **Dark Mode & Color Theme Controls**: System auto-detection with anti-flicker pre-load, 3-way toggle (System / Light / Dark), and persistent `localStorage` support.
- **Live DNS Inspector & Resolver Playground**: Interactive query runner (`A`, `AAAA`, `NS`, `SOA`, `TXT`, `MX`, `CAA`, `DNSKEY`, `CNAME`) backed by a lightweight server-side JSON endpoint (`?api=dns`) that performs live DNS lookups and generates syntax-highlighted dig-style outputs.
- **Production Cluster Topology & Specifications**: High-density cards for Node 1 (Primary Master: PowerDNS 4.9, MariaDB 11.8, REST API :8081, Frankfurt) and Node 2 (Secondary Replica: PowerDNS 4.7, SQLite3, Falkenstein) with non-destructive one-click copyable IPv4/IPv6 addresses (`77.90.60.110` & `94.249.188.145`) featuring checkmark animations.
- **Visual Replication Flow Diagram**: Schematic layout illustrating real-time RFC 5936 AXFR & RFC 1996 NOTIFY replication between master and replica nodes, TLS on-demand hooks, and global anycast resolver routing.
- **Domain Delegation Configurator**: Tabbed switcher for standard dual-nameserver setup (recommended for strict registries like DENIC `.de`) and redundant quad-nameserver configuration, with format switches for Plain FQDNs, BIND Zone syntax, Registrar Glue records, and Terraform/OpenTofu HCL.
- **Step-by-Step Registrar Setup Guides**: Concrete delegation walkthroughs for Cloudflare, Hetzner, INWX, Namecheap, Porkbun, and OVHcloud.
- **Standardized Zone Templates**: Pre-engineered DNS record templates from PowerDNS-Admin (Default Standard Web, Web + Hardened Mail, High-Security DNSSEC/CAA, and Authoritative Only).
- **Architectural Showcase**: Highlights dual-node IP diversity on AS215365, AXFR supermaster replication (<500ms), native DNSSEC ECDSA P-256 signing, heterogeneous databases, Caddy on-demand TLS validation, and open-source dual mirrors.
- **Self-Hosting & Deployment Hub**: Copyable commands and tabs for Debian/Ubuntu zero-touch VPS installation, Docker Compose orchestration, and PowerDNS HTTP REST API curl examples.
- **Technical FAQ**: Expandable accordion clarifying DENIC NAST rule 107 IP uniqueness, zone replication latency, DNSSEC defaults, and admin portal access.
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

# Test live DNS query API
php -r '$_GET["api"] = "dns"; $_GET["domain"] = "example-dns.com"; $_GET["type"] = "A"; require "apps/web/index.php";'
```
