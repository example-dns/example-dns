# example-dns Web Interface (`example-dns.com`)

This directory houses the PHP-powered web landing page and portal for `example-dns.com`.

## Features

- **PHP-powered**: Lightweight server-side rendering with zero dependencies (no npm, Node.js, or build pipelines required)
- **Internationalization (i18n)**: English (`en`) and German (`de`) with auto browser-language detection and manual switcher (`?locale=en`, `?locale=de`)
- **SEO & Social Optimization**: Comprehensive meta tags, Open Graph, Twitter Cards, `hreflang` alternates, canonical links, and Schema.org JSON-LD structured data
- **Modern Minimalist Styles**: Lightweight native lightmode with zero CSS frameworks, responsive typography, and focused micro-interactions (button color transitions and moving SVG arrow on hover)
- **Direct Imprint Link**: `https://ternis.dev/{locale}/legal/imprint`
- **Semantic HTML**: Clean, accessible, semantic structure (`<header>`, `<main>`, `<section>`, `<article>`, `<footer>`)

## Local Development

Run using PHP's built-in web server:

```bash
# From repository root
php -S localhost:8080 -t apps/web
```

Then visit `http://localhost:8080` in your browser.
