# example-dns Web Interface (`example-dns.com`)

This directory houses the static HTML landing page and public interface for `example-dns.com`.

## Features

- **HTML only**: Zero dependencies, no runtime (no PHP, Node.js, or npm required)
- Multi-language support:
  - English: [`index.html`](index.html)
  - German: [`de.html`](de.html)
- Direct legal imprint integration:
  - English: `https://ternis.dev/en/legal/imprint`
  - German: `https://ternis.dev/de/legal/imprint`
- Lightweight, modern, responsive CSS in [`assets/css/style.css`](assets/css/style.css)

## Local Preview

Open [`index.html`](index.html) directly in any web browser, or serve with any static web server (e.g. Python, Caddy, Nginx):

```bash
# Using Python's built-in HTTP server
python3 -m http.server 8080 -d apps/web
```
