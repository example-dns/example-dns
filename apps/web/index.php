<?php
declare(strict_types=1);

$allowed_locales = ['en', 'de'];
$locale = 'en';

if (isset($_GET['locale']) && in_array(strtolower((string)$_GET['locale']), $allowed_locales, true)) {
    $locale = strtolower((string)$_GET['locale']);
} elseif (isset($_GET['lang']) && in_array(strtolower((string)$_GET['lang']), $allowed_locales, true)) {
    $locale = strtolower((string)$_GET['lang']);
} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $accept_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    if (in_array(strtolower($accept_lang), $allowed_locales, true)) {
        $locale = strtolower($accept_lang);
    }
}

$locale_file = __DIR__ . "/locales/{$locale}.php";
if (!file_exists($locale_file)) {
    $locale_file = __DIR__ . '/locales/en.php';
    $locale = 'en';
}

$t = require $locale_file;
$imprint_url = "https://ternis.dev/{$locale}/legal/imprint";
$canonical_url = "https://example-dns.com/?locale=" . urlencode($locale);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($t['tagline'], ENT_QUOTES, 'UTF-8') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($t['seo_keywords'], ENT_QUOTES, 'UTF-8') ?>">
    <meta name="author" content="ternis.org">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="theme-color" content="#ffffff">

    <!-- Canonical & Alternate Links -->
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="alternate" hreflang="en" href="https://example-dns.com/?locale=en">
    <link rel="alternate" hreflang="de" href="https://example-dns.com/?locale=de">
    <link rel="alternate" hreflang="x-default" href="https://example-dns.com/?locale=en">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="example-dns">
    <meta property="og:title" content="<?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($t['tagline'], ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:locale" content="<?= $locale === 'de' ? 'de_DE' : 'en_US' ?>">
    <meta property="og:locale:alternate" content="<?= $locale === 'de' ? 'en_US' : 'de_DE' ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($t['tagline'], ENT_QUOTES, 'UTF-8') ?>">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "WebSite",
                "@id": "https://example-dns.com/#website",
                "url": "https://example-dns.com/",
                "name": "example-dns",
                "description": <?= json_encode($t['tagline'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                "inLanguage": ["en", "de"]
            },
            {
                "@type": "Organization",
                "@id": "https://ternis.org/#organization",
                "name": "ternis.org",
                "url": "https://ternis.org",
                "sameAs": [
                    "https://ternis.dev",
                    "https://ternis.net",
                    "https://github.com/example-dns/example-dns",
                    "https://codeberg.org/example-dns/example-dns"
                ]
            },
            {
                "@type": "SoftwareApplication",
                "name": "example-dns",
                "operatingSystem": "Linux",
                "applicationCategory": "InfrastructureApplication",
                "description": <?= json_encode($t['hero_description'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                "license": "https://opensource.org/licenses/MIT",
                "author": {
                    "@id": "https://ternis.org/#organization"
                }
            }
        ]
    }
    </script>

    <style>
        :root {
            --bg: #ffffff;
            --surface: #f8fafc;
            --surface-elevated: #ffffff;
            --border: #e2e8f0;
            --border-subtle: #edf2f7;
            --border-hover: #cbd5e1;
            --text: #090d16;
            --text-muted: #525e75;
            --text-subtle: #8b98ad;
            --link: #090d16;
            --link-accent: #2563eb;
            --accent: #0f172a;
            --accent-green: #059669;
            --accent-green-bg: #ecfdf5;
            --accent-green-border: #a7f3d0;
            --code-bg: #0f172a;
            --code-text: #f8fafc;
            --code-border: #1e293b;
            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --font-mono: ui-monospace, "SF Mono", "Cascadia Code", "Source Code Pro", Menlo, Monaco, Consolas, monospace;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: var(--font-sans);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .container {
            max-width: 920px;
            margin: 0 auto;
            padding: 2rem 1.5rem 5rem;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 3.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-logo {
            width: 28px;
            height: 28px;
            background-color: var(--accent);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.8125rem;
            font-weight: 800;
            font-family: var(--font-mono);
            letter-spacing: -0.05em;
        }

        .brand-text a {
            font-size: 1.1875rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--text);
            text-decoration: none;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            list-style: none;
        }

        .nav-links a {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .nav-links a:hover {
            color: var(--text);
        }

        .nav-links a.nav-admin-btn {
            color: var(--primary);
            font-weight: 600;
            padding: 0.25rem 0.625rem;
            background-color: var(--primary-light);
            border-radius: var(--radius-sm);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .nav-links a.nav-admin-btn:hover {
            background-color: var(--primary);
            color: #ffffff;
        }

        .lang-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.2rem;
            font-size: 0.75rem;
        }

        .lang-nav a {
            text-decoration: none;
            color: var(--text-muted);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            letter-spacing: 0.02em;
            transition: all 0.15s ease;
        }

        .lang-nav a[aria-current="page"] {
            color: var(--text);
            background-color: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        }

        /* Hero */
        .hero {
            margin-bottom: 4rem;
        }

        .badge-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: var(--accent-green);
            background-color: var(--accent-green-bg);
            border: 1px solid var(--accent-green-border);
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: var(--accent-green);
            box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.2);
            animation: pulse 2.5s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(5, 150, 105, 0); }
            100% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0); }
        }

        .pill-badge {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: var(--text-muted);
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
        }

        .hero h1 {
            font-size: clamp(2.125rem, 4.75vw, 3rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.15;
            color: var(--text);
            margin-bottom: 1.25rem;
        }

        .hero-desc {
            font-size: 1.125rem;
            color: var(--text-muted);
            line-height: 1.65;
            margin-bottom: 2rem;
            max-width: 800px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            line-height: 1;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .btn-primary {
            background-color: var(--text);
            border: 1px solid var(--text);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #1e293b;
            border-color: #1e293b;
        }

        .btn-secondary {
            background-color: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            background-color: #e2e8f0;
            border-color: var(--border-hover);
        }

        .hero-attribution {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .hero-attribution a {
            color: var(--text);
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        /* Stats Strip */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            padding: 1.25rem;
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 4rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
            font-family: var(--font-mono);
        }

        .stat-label {
            font-size: 0.8125rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* Section Commons */
        .content-section {
            margin-bottom: 4.5rem;
            scroll-margin-top: 2rem;
        }

        .section-header {
            margin-bottom: 1.75rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 0.9375rem;
            color: var(--text-muted);
            line-height: 1.55;
            max-width: 720px;
        }

        /* Nodes Grid */
        .nodes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .node-card {
            background-color: var(--surface-elevated);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.375rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
        }

        .node-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .node-name {
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: var(--text-subtle);
            margin-bottom: 0.25rem;
        }

        .node-host {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
            font-family: var(--font-mono);
        }

        .node-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--accent-green);
            background-color: var(--accent-green-bg);
            border: 1px solid var(--accent-green-border);
            border-radius: 9999px;
            padding: 0.2rem 0.5rem;
            white-space: nowrap;
        }

        .node-status-pill::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background-color: var(--accent-green);
        }

        .node-role {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text);
        }

        .node-desc {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .node-specs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            margin-top: auto;
            border-top: 1px solid var(--border-subtle);
            padding-top: 0.75rem;
        }

        .node-specs-table tr {
            border-bottom: 1px solid var(--border-subtle);
        }

        .node-specs-table tr:last-child {
            border-bottom: none;
        }

        .node-specs-table th {
            text-align: left;
            padding: 0.45rem 0;
            font-weight: 500;
            color: var(--text-subtle);
            width: 32%;
            white-space: nowrap;
        }

        .node-specs-table td {
            padding: 0.45rem 0;
            color: var(--text);
            font-family: var(--font-mono);
            font-size: 0.78125rem;
        }

        .copy-inline {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: none;
            border: none;
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
            cursor: pointer;
            color: inherit;
            font-family: inherit;
            font-size: inherit;
            transition: background-color 0.15s ease;
        }

        .copy-inline:hover {
            background-color: var(--surface);
        }

        .copy-inline svg {
            width: 12px;
            height: 12px;
            opacity: 0.5;
            transition: opacity 0.15s ease;
        }

        .copy-inline:hover svg {
            opacity: 1;
        }

        /* Delegation & Tabs */
        .tab-box {
            border: 1px solid var(--border);
            border-radius: 8px;
            background-color: var(--surface-elevated);
            overflow: hidden;
        }

        .tab-headers {
            display: flex;
            border-bottom: 1px solid var(--border);
            background-color: var(--surface);
            overflow-x: auto;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 0.875rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            white-space: nowrap;
            transition: color 0.15s ease, border-color 0.15s ease;
        }

        .tab-btn:hover {
            color: var(--text);
        }

        .tab-btn[aria-selected="true"] {
            color: var(--text);
            background-color: var(--surface-elevated);
            border-bottom-color: var(--text);
        }

        .tab-badge {
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            background-color: var(--border);
            color: var(--text-muted);
        }

        .tab-btn[aria-selected="true"] .tab-badge {
            background-color: #e2e8f0;
            color: var(--text);
        }

        .tab-content {
            padding: 1.5rem;
            display: none;
        }

        .tab-content[data-active="true"] {
            display: block;
        }

        .tab-desc {
            font-size: 0.9375rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        /* Code Display Block */
        .code-block-wrap {
            position: relative;
            background-color: var(--code-bg);
            border: 1px solid var(--code-border);
            border-radius: 6px;
            overflow: hidden;
        }

        .code-block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: rgba(255, 255, 255, 0.04);
            border-bottom: 1px solid var(--code-border);
            font-size: 0.75rem;
            color: #94a3b8;
            font-family: var(--font-mono);
        }

        .code-copy-btn {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f8fafc;
            border-radius: 4px;
            padding: 0.25rem 0.65rem;
            font-size: 0.75rem;
            font-family: var(--font-sans);
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: background-color 0.15s ease;
        }

        .code-copy-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        pre {
            padding: 1rem;
            overflow-x: auto;
            font-family: var(--font-mono);
            font-size: 0.875rem;
            line-height: 1.6;
            color: var(--code-text);
        }

        pre code {
            font-family: inherit;
        }

        .alert-box {
            margin-top: 1.25rem;
            padding: 1rem 1.25rem;
            background-color: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #854d0e;
            line-height: 1.5;
        }

        .alert-box strong {
            color: #713f12;
            font-weight: 600;
        }

        /* Diagnostics Terminal */
        .diag-box {
            background-color: var(--code-bg);
            border: 1px solid var(--code-border);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 2rem;
        }

        .diag-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--code-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.03);
        }

        .diag-dots {
            display: flex;
            gap: 0.35rem;
        }

        .diag-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #334155;
        }

        .diag-title-bar {
            font-size: 0.75rem;
            color: #94a3b8;
            font-family: var(--font-mono);
        }

        .diag-nav {
            display: flex;
            border-bottom: 1px solid var(--code-border);
            background-color: rgba(0, 0, 0, 0.2);
        }

        .diag-tab-btn {
            background: none;
            border: none;
            padding: 0.6rem 1rem;
            font-size: 0.8125rem;
            font-family: var(--font-mono);
            color: #94a3b8;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.15s ease;
        }

        .diag-tab-btn:hover {
            color: #ffffff;
        }

        .diag-tab-btn[aria-selected="true"] {
            color: #38bdf8;
            border-bottom-color: #38bdf8;
            background-color: rgba(255, 255, 255, 0.02);
        }

        .diag-tab-panel {
            display: none;
            padding: 1.25rem 1rem;
        }

        .diag-tab-panel[data-active="true"] {
            display: block;
        }

        .diag-cmd {
            color: #38bdf8;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .diag-output {
            color: #94a3b8;
            font-size: 0.8125rem;
            line-height: 1.5;
        }

        /* Pillars Grid */
        .pillars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
        }

        .pillar-card {
            background-color: var(--surface-elevated);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .pillar-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background-color: var(--surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .pillar-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.01em;
        }

        .pillar-desc {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.55;
        }

        /* Deployment Section */
        .deploy-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        /* Footer */
        footer {
            margin-top: 5rem;
            padding-top: 2.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .footer-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .footer-brand {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            max-width: 400px;
        }

        .footer-brand strong {
            color: var(--text);
            font-size: 1rem;
            font-weight: 700;
        }

        .footer-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.5rem;
        }

        .footer-nav a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .footer-nav a:hover {
            color: var(--text);
            text-decoration: underline;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-subtle);
            font-size: 0.8125rem;
            color: var(--text-subtle);
        }

        .footer-bottom a {
            color: inherit;
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .container {
                padding: 1.5rem 1rem 3rem;
            }
            header {
                margin-bottom: 2.5rem;
            }
            .header-nav {
                width: 100%;
                justify-content: space-between;
            }
            .hero-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="brand">
                <div class="brand-logo" aria-hidden="true">NS</div>
                <div class="brand-text">
                    <a href="?locale=<?= urlencode($locale) ?>">example-dns</a>
                </div>
            </div>

            <div class="header-nav">
                <nav aria-label="Main Navigation">
                    <ul class="nav-links">
                        <li><a href="#network"><?= htmlspecialchars($t['nav_network'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="#delegation"><?= htmlspecialchars($t['nav_delegation'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="#architecture"><?= htmlspecialchars($t['nav_architecture'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="#deploy"><?= htmlspecialchars($t['nav_deployment'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="https://admin.example-dns.com" target="_blank" rel="noopener" class="nav-admin-btn"><?= htmlspecialchars($t['nav_admin'], ENT_QUOTES, 'UTF-8') ?> &rarr;</a></li>
                    </ul>
                </nav>

                <nav class="lang-nav" aria-label="Language selector">
                    <a href="?locale=en"<?= $locale === 'en' ? ' aria-current="page"' : '' ?>>EN</a>
                    <a href="?locale=de"<?= $locale === 'de' ? ' aria-current="page"' : '' ?>>DE</a>
                </nav>
            </div>
        </header>

        <main>
            <!-- Hero -->
            <section class="hero">
                <div class="badge-row">
                    <div class="status-badge">
                        <span class="pulse-dot" aria-hidden="true"></span>
                        <?= htmlspecialchars($t['badge_status'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="pill-badge">
                        <?= htmlspecialchars($t['badge_open_source'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <h1><?= htmlspecialchars($t['hero_title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="hero-desc"><?= htmlspecialchars($t['hero_description'], ENT_QUOTES, 'UTF-8') ?></p>

                <div class="hero-actions">
                    <a href="https://admin.example-dns.com" class="btn btn-primary" target="_blank" rel="noopener">
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="3" width="12" height="10" rx="2"></rect>
                            <circle cx="8" cy="8" r="1"></circle>
                        </svg>
                        <?= htmlspecialchars($t['nav_admin'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a href="#delegation" class="btn btn-secondary">
                        <?= htmlspecialchars($t['cta_delegation'], ENT_QUOTES, 'UTF-8') ?>
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="8" y1="3" x2="8" y2="13"></line>
                            <polyline points="4 9 8 13 12 9"></polyline>
                        </svg>
                    </a>
                    <a href="https://github.com/example-dns/example-dns" class="btn btn-secondary" target="_blank" rel="noopener">
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true">
                            <path d="M8 0C3.58 0 0 3.58 0 8a8 8 0 005.47 7.59c.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0016 8c0-4.42-3.58-8-8-8z"/>
                        </svg>
                        <?= htmlspecialchars($t['cta_github'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a href="https://codeberg.org/example-dns/example-dns" class="btn btn-secondary" target="_blank" rel="noopener">
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true">
                            <path d="M7.998.002a8 8 0 00-7.996 8 8 8 0 002.342 5.656c.154.148.33.155.45.02.12-.137.07-.323-.085-.472A7.37 7.37 0 01.625 8a7.375 7.375 0 017.375-7.375 7.375 7.375 0 017.375 7.375 7.37 7.37 0 01-2.086 5.204c-.156.149-.205.335-.085.472.12.135.296.128.45-.02A8 8 0 0016 8.002a8 8 0 00-8.002-8zM7.9 3.5a4.5 4.5 0 00-4.5 4.5 4.5 4.5 0 00.32 1.666L7.9 4.962l4.18 4.704A4.5 4.5 0 0012.4 8a4.5 4.5 0 00-4.5-4.5z"/>
                        </svg>
                        <?= htmlspecialchars($t['cta_codeberg'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>

                <p class="hero-attribution"><?= $t['project_by'] ?></p>
            </section>

            <!-- Quick Metrics Strip -->
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?= htmlspecialchars($t['stat_asns_value'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="stat-label"><?= htmlspecialchars($t['stat_asns_label'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= htmlspecialchars($t['stat_ns_value'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="stat-label"><?= htmlspecialchars($t['stat_ns_label'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= htmlspecialchars($t['stat_sync_value'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="stat-label"><?= htmlspecialchars($t['stat_sync_label'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= htmlspecialchars($t['stat_compliance_value'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="stat-label"><?= htmlspecialchars($t['stat_compliance_label'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <!-- Cluster Topology Section -->
            <section id="network" class="content-section" aria-labelledby="network-heading">
                <div class="section-header">
                    <h2 id="network-heading" class="section-title"><?= htmlspecialchars($t['network_section_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="section-subtitle"><?= htmlspecialchars($t['network_section_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="nodes-grid">
                    <!-- Node 1 -->
                    <article class="node-card">
                        <div class="node-card-header">
                            <div>
                                <div class="node-name"><?= htmlspecialchars($t['node_primary_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="node-host"><?= htmlspecialchars($t['node_primary_host'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <span class="node-status-pill"><?= htmlspecialchars($t['lbl_status_online'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="node-role"><?= htmlspecialchars($t['node_primary_role'], ENT_QUOTES, 'UTF-8') ?></div>
                        <p class="node-desc"><?= htmlspecialchars($t['node_primary_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        
                        <table class="node-specs-table">
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv4'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <button type="button" class="copy-inline" data-copy="77.90.60.110" title="Copy IPv4">
                                        <span>77.90.60.110</span>
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv6'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <button type="button" class="copy-inline" data-copy="2a14:7c0:1002:16c2::" title="Copy IPv6">
                                        <span>2a14:7c0:1002:16c2::</span>
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_asn'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>77.90.60.0/24 (AS215365)</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_engine'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>PowerDNS 4.9 / MariaDB 11.8</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_interop'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>one.ns.ternis.net</td>
                            </tr>
                        </table>
                    </article>

                    <!-- Node 2 -->
                    <article class="node-card">
                        <div class="node-card-header">
                            <div>
                                <div class="node-name"><?= htmlspecialchars($t['node_secondary_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="node-host"><?= htmlspecialchars($t['node_secondary_host'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <span class="node-status-pill"><?= htmlspecialchars($t['lbl_status_online'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="node-role"><?= htmlspecialchars($t['node_secondary_role'], ENT_QUOTES, 'UTF-8') ?></div>
                        <p class="node-desc"><?= htmlspecialchars($t['node_secondary_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        
                        <table class="node-specs-table">
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv4'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <button type="button" class="copy-inline" data-copy="94.249.188.145" title="Copy IPv4">
                                        <span>94.249.188.145</span>
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv6'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <button type="button" class="copy-inline" data-copy="2a14:7c0:1002:169c::" title="Copy IPv6">
                                        <span>2a14:7c0:1002:169c::</span>
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_asn'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>94.249.188.0/24 (AS215365)</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_engine'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>PowerDNS 4.7 / SQLite3</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_interop'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>two.ns.ternis.net</td>
                            </tr>
                        </table>
                    </article>

                    <!-- Web Portal Node -->
                    <article class="node-card">
                        <div class="node-card-header">
                            <div>
                                <div class="node-name"><?= htmlspecialchars($t['node_web_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="node-host"><?= htmlspecialchars($t['node_web_host'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <span class="node-status-pill"><?= htmlspecialchars($t['lbl_status_online'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="node-role"><?= htmlspecialchars($t['node_web_role'], ENT_QUOTES, 'UTF-8') ?></div>
                        <p class="node-desc"><?= htmlspecialchars($t['node_web_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        
                        <table class="node-specs-table">
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv4'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <button type="button" class="copy-inline" data-copy="77.90.60.110" title="Copy IPv4">
                                        <span>77.90.60.110</span>
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv6'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <button type="button" class="copy-inline" data-copy="2a14:7c0:1002:16c2::" title="Copy IPv6">
                                        <span>2a14:7c0:1002:16c2::</span>
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th>Web Server</th>
                                <td>Caddy v2.10 (Auto TLS)</td>
                            </tr>
                            <tr>
                                <th>Backend</th>
                                <td>PHP 8.5 Native Service</td>
                            </tr>
                            <tr>
                                <th>On-Demand TLS</th>
                                <td>Dynamic PowerDNS Ask Hook</td>
                            </tr>
                        </table>
                    </article>
                </div>
            </section>

            <!-- Domain Delegation Section -->
            <section id="delegation" class="content-section" aria-labelledby="delegation-heading">
                <div class="section-header">
                    <h2 id="delegation-heading" class="section-title"><?= htmlspecialchars($t['delegation_section_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="section-subtitle"><?= htmlspecialchars($t['delegation_section_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="tab-box">
                    <div class="tab-headers" role="tablist" aria-label="Delegation options">
                        <button type="button" role="tab" class="tab-btn" id="tab-dual" aria-selected="true" aria-controls="panel-dual" data-tab="panel-dual">
                            <span><?= htmlspecialchars($t['tab_dual_title'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="tab-badge"><?= htmlspecialchars($t['tab_dual_badge'], ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <button type="button" role="tab" class="tab-btn" id="tab-quad" aria-selected="false" aria-controls="panel-quad" data-tab="panel-quad">
                            <span><?= htmlspecialchars($t['tab_quad_title'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="tab-badge"><?= htmlspecialchars($t['tab_quad_badge'], ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                    </div>

                    <!-- Panel Dual-NS -->
                    <div id="panel-dual" role="tabpanel" aria-labelledby="tab-dual" class="tab-content" data-active="true">
                        <p class="tab-desc"><?= htmlspecialchars($t['tab_dual_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        
                        <div class="code-block-wrap">
                            <div class="code-block-header">
                                <span>DNS NS DELEGATION</span>
                                <button type="button" class="code-copy-btn" data-copy-target="code-ns-dual">
                                    <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            </div>
                            <pre><code id="code-ns-dual">example-dns.net
example-dns.org</code></pre>
                        </div>

                        <div class="alert-box">
                            <strong><?= htmlspecialchars($t['delegation_note_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?= htmlspecialchars($t['delegation_note_body'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <!-- Panel Quad-NS -->
                    <div id="panel-quad" role="tabpanel" aria-labelledby="tab-quad" class="tab-content" data-active="false">
                        <p class="tab-desc"><?= htmlspecialchars($t['tab_quad_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        
                        <div class="code-block-wrap">
                            <div class="code-block-header">
                                <span>DNS NS DELEGATION (4-NS)</span>
                                <button type="button" class="code-copy-btn" data-copy-target="code-ns-quad">
                                    <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            </div>
                            <pre><code id="code-ns-quad">example-dns.net
example-dns.org
one.ns.ternis.net
two.ns.ternis.net</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Live DNS Diagnostics Box -->
                <div class="diag-box">
                    <div class="diag-header">
                        <div class="diag-dots">
                            <span class="diag-dot"></span>
                            <span class="diag-dot"></span>
                            <span class="diag-dot"></span>
                        </div>
                        <div class="diag-title-bar"><?= htmlspecialchars($t['diag_title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div></div>
                    </div>

                    <div class="diag-nav" role="tablist">
                        <button type="button" role="tab" class="diag-tab-btn" id="diag-btn-soa" aria-selected="true" data-diag="diag-soa">
                            <?= htmlspecialchars($t['diag_tab_soa'], ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <button type="button" role="tab" class="diag-tab-btn" id="diag-btn-ns" aria-selected="false" data-diag="diag-ns">
                            <?= htmlspecialchars($t['diag_tab_ns'], ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <button type="button" role="tab" class="diag-tab-btn" id="diag-btn-dnssec" aria-selected="false" data-diag="diag-dnssec">
                            <?= htmlspecialchars($t['diag_tab_dnssec'], ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>

                    <div id="diag-soa" role="tabpanel" class="diag-tab-panel" data-active="true">
                        <div class="diag-cmd">
                            <span>$ dig @example-dns.net example-dns.com SOA +dnssec</span>
                            <button type="button" class="code-copy-btn" data-copy="dig @example-dns.net example-dns.com SOA +dnssec">
                                <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>
                        <pre class="diag-output">;; ANSWER SECTION:
example-dns.com.      3600  IN  SOA  example-dns.net. hostmaster.ternis.org. (
                                2026090901 ; serial
                                10800      ; refresh (3 hours)
                                3600       ; retry (1 hour)
                                604800     ; expire (1 week)
                                3600       ; minimum (1 hour)
                                )
example-dns.com.      3600  IN  RRSIG SOA 13 2 3600 20260923000000 20260909000000 ...</pre>
                    </div>

                    <div id="diag-ns" role="tabpanel" class="diag-tab-panel" data-active="false">
                        <div class="diag-cmd">
                            <span>$ dig @example-dns.org example-dns.com NS +short</span>
                            <button type="button" class="code-copy-btn" data-copy="dig @example-dns.org example-dns.com NS +short">
                                <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>
                        <pre class="diag-output">example-dns.net.
example-dns.org.</pre>
                    </div>

                    <div id="diag-dnssec" role="tabpanel" class="diag-tab-panel" data-active="false">
                        <div class="diag-cmd">
                            <span>$ dig @example-dns.net example-dns.com DNSKEY +multiline</span>
                            <button type="button" class="code-copy-btn" data-copy="dig @example-dns.net example-dns.com DNSKEY +multiline">
                                <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>
                        <pre class="diag-output">;; ANSWER SECTION:
example-dns.com.      3600 IN DNSKEY 256 3 13 (
                                ovpY.../3d8g==
                                ) ; ZSK; alg = ECDSAP256SHA256 ; key id = 48201
example-dns.com.      3600 IN DNSKEY 257 3 13 (
                                sD7p...VqLg==
                                ) ; KSK; alg = ECDSAP256SHA256 ; key id = 29314</pre>
                    </div>
                </div>
            </section>

            <!-- Architectural Foundation Section -->
            <section id="architecture" class="content-section" aria-labelledby="architecture-heading">
                <div class="section-header">
                    <h2 id="architecture-heading" class="section-title"><?= htmlspecialchars($t['arch_section_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="section-subtitle"><?= htmlspecialchars($t['arch_section_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="pillars-grid">
                    <article class="pillar-card">
                        <div class="pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                        </div>
                        <h3 class="pillar-title"><?= htmlspecialchars($t['pillar_asn_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="pillar-desc"><?= htmlspecialchars($t['pillar_asn_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="pillar-card">
                        <div class="pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        </div>
                        <h3 class="pillar-title"><?= htmlspecialchars($t['pillar_repl_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="pillar-desc"><?= htmlspecialchars($t['pillar_repl_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="pillar-card">
                        <div class="pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                        <h3 class="pillar-title"><?= htmlspecialchars($t['pillar_dnssec_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="pillar-desc"><?= htmlspecialchars($t['pillar_dnssec_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="pillar-card">
                        <div class="pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                        </div>
                        <h3 class="pillar-title"><?= htmlspecialchars($t['pillar_db_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="pillar-desc"><?= htmlspecialchars($t['pillar_db_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="pillar-card">
                        <div class="pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                        <h3 class="pillar-title"><?= htmlspecialchars($t['pillar_caddy_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="pillar-desc"><?= htmlspecialchars($t['pillar_caddy_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="pillar-card">
                        <div class="pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        </div>
                        <h3 class="pillar-title"><?= htmlspecialchars($t['pillar_oss_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="pillar-desc"><?= htmlspecialchars($t['pillar_oss_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                </div>
            </section>

            <!-- Deployment Quickstart Section -->
            <section id="deploy" class="content-section" aria-labelledby="deploy-heading">
                <div class="section-header">
                    <h2 id="deploy-heading" class="section-title"><?= htmlspecialchars($t['deploy_section_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="section-subtitle"><?= htmlspecialchars($t['deploy_section_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="deploy-grid">
                    <div class="tab-box">
                        <div class="tab-headers" role="tablist">
                            <button type="button" role="tab" class="tab-btn" id="deploy-btn-vps" aria-selected="true" data-deploy="panel-vps">
                                <span><?= htmlspecialchars($t['deploy_tab_vps'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                            <button type="button" role="tab" class="tab-btn" id="deploy-btn-docker" aria-selected="false" data-deploy="panel-docker">
                                <span><?= htmlspecialchars($t['deploy_tab_docker'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>

                        <!-- VPS Installer Panel -->
                        <div id="panel-vps" role="tabpanel" class="tab-content" data-active="true">
                            <p class="tab-desc"><?= htmlspecialchars($t['deploy_vps_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="code-block-wrap">
                                <div class="code-block-header">
                                    <span>BASH ZERO-TOUCH INSTALLER</span>
                                    <button type="button" class="code-copy-btn" data-copy-target="code-vps">
                                        <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                        <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                </div>
                                <pre><code id="code-vps">curl -sSL https://raw.githubusercontent.com/example-dns/example-dns/master/infra/scripts/install-vps.sh | sudo bash -s -- --role all</code></pre>
                            </div>
                        </div>

                        <!-- Docker Compose Panel -->
                        <div id="panel-docker" role="tabpanel" class="tab-content" data-active="false">
                            <p class="tab-desc"><?= htmlspecialchars($t['deploy_docker_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="code-block-wrap">
                                <div class="code-block-header">
                                    <span>DOCKER COMPOSE ORCHESTRATION</span>
                                    <button type="button" class="code-copy-btn" data-copy-target="code-docker">
                                        <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                        <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                </div>
                                <pre><code id="code-docker">git clone https://github.com/example-dns/example-dns.git
cd example-dns/infra
docker compose up -d</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer>
            <div class="footer-top">
                <div class="footer-brand">
                    <strong>example-dns</strong>
                    <p><?= htmlspecialchars($t['footer_tagline'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <nav class="footer-nav" aria-label="Footer links">
                    <a href="<?= htmlspecialchars($imprint_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($t['footer_imprint'], ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="https://github.com/example-dns/example-dns" target="_blank" rel="noopener"><?= htmlspecialchars($t['footer_github'], ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="https://codeberg.org/example-dns/example-dns" target="_blank" rel="noopener"><?= htmlspecialchars($t['footer_codeberg'], ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="https://github.com/example-dns/example-dns/blob/master/docs/architecture.md" target="_blank" rel="noopener"><?= htmlspecialchars($t['footer_docs'], ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="https://ternis.org" target="_blank" rel="noopener">ternis.org</a>
                </nav>
            </div>

            <div class="footer-bottom">
                <div><?= $t['footer_license'] ?></div>
                <div><?= $t['footer_maintainer'] ?></div>
            </div>
        </footer>
    </div>

    <!-- Minimalist interaction handling (zero frameworks) -->
    <script>
    (function () {
        'use strict';

        var copiedText = <?= json_encode($t['copied_label'], JSON_UNESCAPED_UNICODE) ?>;
        var copyText = <?= json_encode($t['copy_label'], JSON_UNESCAPED_UNICODE) ?>;

        function copyString(str, btn) {
            if (!navigator.clipboard) {
                var ta = document.createElement('textarea');
                ta.value = str;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta);
            } else {
                navigator.clipboard.writeText(str);
            }

            var textElem = btn.querySelector('.btn-text') || btn.querySelector('span') || btn;
            var orig = textElem.textContent;
            textElem.textContent = copiedText;
            btn.style.opacity = '0.7';

            setTimeout(function () {
                textElem.textContent = orig;
                btn.style.opacity = '';
            }, 1800);
        }

        // Generic delegation copy buttons
        document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-copy-target');
                var target = document.getElementById(targetId);
                if (target) {
                    copyString(target.textContent.trim(), btn);
                }
            });
        });

        // Direct data-copy buttons
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var str = btn.getAttribute('data-copy');
                if (str) {
                    copyString(str, btn);
                }
            });
        });

        // Tab switcher generic
        function setupTabs(btnAttr, panelAttr) {
            var btns = document.querySelectorAll('[' + btnAttr + ']');
            btns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var targetPanelId = btn.getAttribute(btnAttr);
                    var container = btn.closest('.tab-box');
                    if (!container) return;

                    container.querySelectorAll('[' + btnAttr + ']').forEach(function (b) {
                        b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
                    });

                    container.querySelectorAll('.tab-content').forEach(function (panel) {
                        panel.setAttribute('data-active', panel.id === targetPanelId ? 'true' : 'false');
                    });
                });
            });
        }

        setupTabs('data-tab', 'id');
        setupTabs('data-deploy', 'id');

        // Diagnostics Tab switcher
        var diagBtns = document.querySelectorAll('.diag-tab-btn');
        diagBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-diag');
                diagBtns.forEach(function (b) {
                    b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
                });
                document.querySelectorAll('.diag-tab-panel').forEach(function (p) {
                    p.setAttribute('data-active', p.id === targetId ? 'true' : 'false');
                });
            });
        });
    })();
    </script>
</body>
</html>
