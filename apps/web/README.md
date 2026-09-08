# example-dns Web Interface (`example-dns.com`)

This directory houses the PHP-powered web landing page and portal for `example-dns.com`.

## Features

- **PHP-powered**: Lightweight server-side rendering with zero dependencies (no npm, Node.js, or client-side JavaScript required)
- **Internationalization (i18n)**: English (`en`) and German (`de`) with auto browser-language detection and manual switcher (`?locale=en`, `?locale=de`)
- **Direct Imprint Link**: `https://ternis.dev/{locale}/legal/imprint`
- **Pure Semantic HTML**: No CSS stylesheets, clean accessible markup

## Local Development

Run using PHP's built-in web server:

```bash
# From repository root
php -S localhost:8080 -t apps/web
```

Then visit `http://localhost:8080` in your browser.
