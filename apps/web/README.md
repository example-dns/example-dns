# example-dns Web Interface (`example-dns.com`)

This directory houses the frontend web landing page, user portal, and interface for `example-dns.com`.

## Features

- Lightweight, pure PHP implementation (no npm or Node.js dependencies)
- Internationalization support: English (`en`) and German (`de`)
- Auto-detection of browser language with manual toggle (`?locale=en`, `?locale=de`)
- Dynamic legal imprint link pointing to `https://ternis.dev/{locale}/legal/imprint`
- Modern, responsive dark-mode styling

## Local Development

Run with the PHP built-in web server:

```bash
# From apps/web/ directory
php -S localhost:8080

# Or from repository root
php -S localhost:8080 -t apps/web
```

Then visit `http://localhost:8080` in your browser.
