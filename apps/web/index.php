<?php
declare(strict_types=1);

$allowed_locales = ['en', 'de'];
$locale = 'en';

if (isset($_GET['locale']) && in_array(strtolower((string)$_GET['locale']), $allowed_locales, true)) {
    $locale = strtolower((string)$_GET['locale']);
} elseif (isset($_GET['lang']) && in_array(strtolower((string)$_GET['lang']), $allowed_locales, true)) {
    $locale = strtolower((string)$_GET['lang']);
} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $accept_lang = substr((string)$_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
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
    <meta name="robots" content="index, follow">
    <meta name="color-scheme" content="light">
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
            }
        ]
    }
    </script>

    <style>
        :root {
            color-scheme: light;
            --bg: #ffffff;
            --surface: #f8fafc;
            --surface-elevated: #ffffff;
            --border: #e2e8f0;
            --border-subtle: #edf2f7;
            --border-hover: #cbd5e1;
            --text: #0f172a;
            --text-muted: #525e75;
            --link: #2563eb;
            --primary: #0f172a;
            --primary-hover: #1e293b;
            --primary-text: #ffffff;
            --radius-sm: 6px;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
        }

        /* Modern Staggered Load-In Animations */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeUp 0.65s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-1 { animation-delay: 0.04s; }
        .delay-2 { animation-delay: 0.10s; }
        .delay-3 { animation-delay: 0.16s; }
        .delay-4 { animation-delay: 0.22s; }
        .delay-5 { animation-delay: 0.28s; }
        .delay-6 { animation-delay: 0.34s; }

        .skip-link {
            position: absolute;
            top: -100px;
            left: 1.5rem;
            background: #2563eb;
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            z-index: 1000;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: top 0.2s ease;
        }
        .skip-link:focus {
            top: 1rem;
        }

        .container {
            max-width: 720px;
            width: 100%;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .lang-nav {
            display: inline-flex;
            align-items: center;
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0.15rem;
            font-size: 0.75rem;
            gap: 0.15rem;
        }

        .lang-nav a {
            text-decoration: none;
            color: var(--text-muted);
            padding: 0.2rem 0.55rem;
            border-radius: 4px;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .lang-nav a[aria-current="page"] {
            color: var(--text);
            background-color: var(--surface-elevated);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .hero {
            margin: auto 0;
            padding: 3rem 0;
        }

        .hero-title {
            font-size: clamp(2.5rem, 6vw, 3.5rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.1;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .hero-desc {
            font-size: clamp(1.0625rem, 2vw, 1.25rem);
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1.75rem;
            max-width: 620px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.75rem;
        }

        /* Tactical Button Micro-Interactions */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.58rem 1.2rem;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1;
            border-radius: var(--radius-sm);
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.15s cubic-bezier(0.16, 1, 0.3, 1),
                        background-color 0.15s ease,
                        border-color 0.15s ease,
                        box-shadow 0.15s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.1);
        }

        .btn:active {
            transform: translateY(0) scale(0.98);
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--primary-text);
            border: 1px solid var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            border-color: var(--border-hover);
            background-color: var(--border-subtle);
        }

        .hero-attribution {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .hero-attribution a {
            color: var(--text);
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .hero-attribution a:hover {
            color: var(--link);
        }

        footer {
            padding-top: 1.75rem;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        footer a {
            color: var(--text);
            text-decoration: underline;
            text-underline-offset: 2px;
            transition: opacity 0.15s ease;
        }

        footer a:hover {
            opacity: 0.8;
        }

        .footer-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
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

        /* Accessibility: Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link"><?= htmlspecialchars($t['skip_to_content'], ENT_QUOTES, 'UTF-8') ?></a>

    <div class="container">
        <!-- Header -->
        <header class="fade-in delay-1">
            <nav class="lang-nav" aria-label="Language selector">
                <a href="?locale=en"<?= $locale === 'en' ? ' aria-current="page"' : '' ?>>EN</a>
                <a href="?locale=de"<?= $locale === 'de' ? ' aria-current="page"' : '' ?>>DE</a>
            </nav>
        </header>

        <!-- Hero -->
        <main id="main-content" class="hero">
            <h1 class="hero-title fade-in delay-2"><?= htmlspecialchars($t['hero_title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="hero-desc fade-in delay-3"><?= htmlspecialchars($t['hero_description'], ENT_QUOTES, 'UTF-8') ?></p>

            <div class="hero-actions fade-in delay-4">
                <a href="https://admin.example-dns.com" class="btn btn-primary" target="_blank" rel="noopener">
                    <?= htmlspecialchars($t['cta_admin'], ENT_QUOTES, 'UTF-8') ?> &rarr;
                </a>
                <a href="https://github.com/example-dns/example-dns" class="btn btn-secondary" target="_blank" rel="noopener">
                    <?= htmlspecialchars($t['cta_github'], ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a href="https://codeberg.org/example-dns/example-dns" class="btn btn-secondary" target="_blank" rel="noopener">
                    <?= htmlspecialchars($t['cta_codeberg'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>

            <p class="hero-attribution fade-in delay-5"><?= $t['project_by'] ?></p>
        </main>

        <!-- Footer -->
        <footer class="fade-in delay-6">
            <p><?= $t['footer_text'] ?></p>
            <nav class="footer-nav" aria-label="Footer links">
                <a href="<?= htmlspecialchars($imprint_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($t['imprint'], ENT_QUOTES, 'UTF-8') ?></a>
                <a href="https://admin.example-dns.com" target="_blank" rel="noopener">Admin</a>
                <a href="https://github.com/example-dns/example-dns" target="_blank" rel="noopener">GitHub</a>
                <a href="https://codeberg.org/example-dns/example-dns" target="_blank" rel="noopener">Codeberg</a>
                <a href="https://ternis.org" target="_blank" rel="noopener">ternis.org</a>
            </nav>
        </footer>
    </div>
</body>
</html>
