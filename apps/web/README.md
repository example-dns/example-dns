# example-dns Web Interface (`example-dns.com`)

This directory houses the lightweight, minimal PHP-powered web landing page and portal for `example-dns.com`.

## Features

- **Minimalist & Zero-Dependency Architecture**: Pure server-side PHP with semantic HTML5, zero CSS frameworks, zero JavaScript build pipelines, and zero runtime dependencies.
- **Pure Typographic Identity**: Clean text-only brand identity without brand logos.
- **Focused Hero-Only Layout**: Streamlined single-viewport presentation highlighting the brand identity and maintainer attribution.
- **Full Internationalization (i18n)**: English (`en`) and German (`de`) with 1:1 key parity, automatic browser-language detection, and manual switching (`?locale=en`, `?locale=de`).
- **Responsive & Minimalist Light Mode**: Clean, semantic stylesheet configured exclusively for high-contrast light mode.
- **Direct Action Links**: Quick access to the Admin Portal (`https://admin.example-dns.com`), GitHub, and Codeberg.
- **SEO & Canonical Links**: Canonical links, `hreflang` tags, and Open Graph metadata.
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
