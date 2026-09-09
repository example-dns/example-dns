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
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --link: #2563eb;
            --btn-primary-bg: #0f172a;
            --btn-primary-border: #0f172a;
            --btn-primary-text: #ffffff;
            --btn-primary-hover-bg: #1e293b;
            --btn-primary-hover-border: #1e293b;
            --btn-secondary-bg: #f8fafc;
            --btn-secondary-border: #cbd5e1;
            --btn-secondary-text: #0f172a;
            --btn-secondary-hover-bg: #e2e8f0;
            --btn-secondary-hover-border: #94a3b8;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .container {
            max-width: 860px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 3rem;
        }

        .brand {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand a {
            color: var(--text);
            text-decoration: none;
        }

        .lang-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.2rem 0.3rem;
            font-size: 0.8125rem;
        }

        .lang-nav a {
            text-decoration: none;
            color: var(--text-muted);
            padding: 0.2rem 0.55rem;
            border-radius: 4px;
            font-weight: 500;
        }

        .lang-nav a[aria-current="page"] {
            color: var(--text);
            background-color: #ffffff;
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        a {
            color: var(--link);
            text-decoration: underline;
            text-underline-offset: 2.5px;
        }

        .hero {
            margin-bottom: 3.5rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #065f46;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            margin-bottom: 1.25rem;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #10b981;
        }

        .hero h1 {
            font-size: clamp(2rem, 4.5vw, 2.75rem);
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.15;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .hero-desc {
            font-size: 1.125rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.75rem;
        }

        /* Buttons: the ONLY elements with :hover effects */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            line-height: 1;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .btn-primary {
            background-color: var(--btn-primary-bg);
            border: 1px solid var(--btn-primary-border);
            color: var(--btn-primary-text);
        }

        .btn-primary:hover {
            background-color: var(--btn-primary-hover-bg);
            border-color: var(--btn-primary-hover-border);
        }

        .btn-secondary {
            background-color: var(--btn-secondary-bg);
            border: 1px solid var(--btn-secondary-border);
            color: var(--btn-secondary-text);
        }

        .btn-secondary:hover {
            background-color: var(--btn-secondary-hover-bg);
            border-color: var(--btn-secondary-hover-border);
        }

        .btn-arrow {
            display: inline-block;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn:hover .btn-arrow {
            transform: translateX(4px);
        }

        .hero-attribution {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .section-title {
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
            margin-bottom: 1.25rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 3.5rem;
        }

        .card {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.01em;
        }

        .card-badge {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            background-color: #e2e8f0;
            color: #334155;
            white-space: nowrap;
        }

        .card-desc {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.55;
        }

        footer {
            padding-top: 2rem;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .footer-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
        }

        .footer-nav a {
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="brand">
                <a href="?locale=<?= urlencode($locale) ?>">example-dns</a>
            </div>
            <nav class="lang-nav" aria-label="Language selector">
                <a href="?locale=en"<?= $locale === 'en' ? ' aria-current="page"' : '' ?>>EN</a>
                <a href="?locale=de"<?= $locale === 'de' ? ' aria-current="page"' : '' ?>>DE</a>
            </nav>
        </header>

        <main>
            <section class="hero">
                <div class="badge">
                    <span class="badge-dot" aria-hidden="true"></span>
                    <?= htmlspecialchars($t['badge_open_source'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <h1><?= htmlspecialchars($t['hero_heading'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="hero-desc"><?= htmlspecialchars($t['hero_description'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="hero-actions">
                    <a href="https://github.com/example-dns/example-dns" class="btn btn-primary" target="_blank" rel="noopener">
                        <?= htmlspecialchars($t['view_on_github'], ENT_QUOTES, 'UTF-8') ?>
                        <svg class="btn-arrow" viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="3" y1="8" x2="13" y2="8"></line>
                            <polyline points="9 4 13 8 9 12"></polyline>
                        </svg>
                    </a>
                    <a href="https://codeberg.org/example-dns/example-dns" class="btn btn-secondary" target="_blank" rel="noopener">
                        <?= htmlspecialchars($t['view_on_codeberg'], ENT_QUOTES, 'UTF-8') ?>
                        <svg class="btn-arrow" viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="3" y1="8" x2="13" y2="8"></line>
                            <polyline points="9 4 13 8 9 12"></polyline>
                        </svg>
                    </a>
                </div>
                <p class="hero-attribution"><?= $t['project_by'] ?></p>
            </section>

            <section aria-labelledby="ecosystem-heading">
                <h2 id="ecosystem-heading" class="section-title"><?= htmlspecialchars($t['infrastructure_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="cards-grid">
                    <article class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?= htmlspecialchars($t['domain_web_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="card-badge"><?= htmlspecialchars($t['domain_web_badge'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="card-desc"><?= htmlspecialchars($t['domain_web_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?= htmlspecialchars($t['domain_primary_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="card-badge"><?= htmlspecialchars($t['domain_primary_badge'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="card-desc"><?= htmlspecialchars($t['domain_primary_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?= htmlspecialchars($t['domain_secondary_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="card-badge"><?= htmlspecialchars($t['domain_secondary_badge'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="card-desc"><?= htmlspecialchars($t['domain_secondary_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                </div>
            </section>

            <section aria-labelledby="features-heading">
                <h2 id="features-heading" class="section-title"><?= htmlspecialchars($t['features_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="cards-grid">
                    <article class="card">
                        <h3 class="card-title"><?= htmlspecialchars($t['feature_perf_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="card-desc"><?= htmlspecialchars($t['feature_perf_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="card">
                        <h3 class="card-title"><?= htmlspecialchars($t['feature_oss_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="card-desc"><?= htmlspecialchars($t['feature_oss_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="card">
                        <h3 class="card-title"><?= htmlspecialchars($t['feature_standards_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="card-desc"><?= htmlspecialchars($t['feature_standards_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                </div>
            </section>
        </main>

        <footer>
            <p><?= $t['footer_text'] ?></p>
            <nav class="footer-nav" aria-label="Footer links">
                <a href="<?= htmlspecialchars($imprint_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($t['imprint'], ENT_QUOTES, 'UTF-8') ?></a>
                <a href="https://github.com/example-dns/example-dns" target="_blank" rel="noopener">GitHub</a>
                <a href="https://codeberg.org/example-dns/example-dns" target="_blank" rel="noopener">Codeberg</a>
                <a href="https://ternis.org" target="_blank" rel="noopener">ternis.org</a>
            </nav>
        </footer>
    </div>
</body>
</html>
