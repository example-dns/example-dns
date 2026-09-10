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
            --bg: #ffffff;
            --surface: #f8fafc;
            --surface-elevated: #ffffff;
            --border: #e2e8f0;
            --border-subtle: #edf2f7;
            --text: #0f172a;
            --text-muted: #525e75;
            --text-subtle: #8b98ad;
            --link: #2563eb;
            --primary: #0f172a;
            --primary-hover: #1e293b;
            --primary-text: #ffffff;
            --primary-light: rgba(37, 99, 235, 0.08);
            --primary-border: rgba(37, 99, 235, 0.2);
            --code-bg: #0b1120;
            --code-text: #f1f5f9;
            --code-border: #1e293b;
            --radius-sm: 6px;
            --radius-md: 10px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #090d16;
                --surface: #0f172a;
                --surface-elevated: #162032;
                --border: #1e293b;
                --border-subtle: #162032;
                --text: #f8fafc;
                --text-muted: #94a3b8;
                --text-subtle: #64748b;
                --link: #60a5fa;
                --primary: #f8fafc;
                --primary-hover: #e2e8f0;
                --primary-text: #090d16;
                --primary-light: rgba(59, 130, 246, 0.15);
                --primary-border: rgba(59, 130, 246, 0.35);
                --code-bg: #060a12;
                --code-text: #f1f5f9;
                --code-border: #1e293b;
            }
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
        }

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
        }
        .skip-link:focus {
            top: 1rem;
        }

        .container {
            max-width: 820px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 3rem;
            gap: 1rem;
        }

        /* Pure typographic brand — NO logo forever */
        .brand {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .brand a {
            color: var(--text);
            text-decoration: none;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-admin-btn {
            color: #2563eb;
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 0.25rem 0.65rem;
            background-color: var(--primary-light);
            border: 1px solid var(--primary-border);
            border-radius: var(--radius-sm);
            text-decoration: none;
            transition: opacity 0.15s ease;
        }

        .nav-admin-btn:hover {
            opacity: 0.85;
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
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .lang-nav a[aria-current="page"] {
            color: var(--text);
            background-color: var(--surface-elevated);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .hero {
            margin-bottom: 3rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #059669;
            background-color: rgba(5, 150, 105, 0.1);
            border: 1px solid rgba(5, 150, 105, 0.25);
            border-radius: 9999px;
            padding: 0.2rem 0.65rem;
            margin-bottom: 1.25rem;
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #059669;
        }

        .hero h1 {
            font-size: clamp(2rem, 4.5vw, 2.75rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.15;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .hero-desc {
            font-size: 1.0625rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1.15rem;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1;
            border-radius: var(--radius-sm);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--primary-text);
            border: 1px solid var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            border-color: var(--text-muted);
        }

        .hero-attribution {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .hero-attribution a {
            color: var(--text);
            text-decoration: underline;
        }

        .content-section {
            margin-bottom: 2.75rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .section-desc {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        /* Delegation code block */
        .code-box {
            background-color: var(--code-bg);
            border: 1px solid var(--code-border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            position: relative;
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.875rem;
            border-bottom: 1px solid var(--code-border);
            font-size: 0.75rem;
            font-family: ui-monospace, "SF Mono", monospace;
            color: #94a3b8;
        }

        .copy-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f8fafc;
            border-radius: 4px;
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
        }

        pre {
            padding: 0.875rem;
            font-family: ui-monospace, "SF Mono", monospace;
            font-size: 0.8125rem;
            color: var(--code-text);
            line-height: 1.6;
            overflow-x: auto;
        }

        /* Nodes Grid */
        .nodes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1rem;
        }

        .node-card {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 1.125rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .node-role {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-subtle);
        }

        .node-host {
            font-size: 1.0625rem;
            font-weight: 700;
            color: var(--text);
            font-family: ui-monospace, "SF Mono", monospace;
        }

        .node-desc {
            font-size: 0.8125rem;
            color: var(--text-muted);
            line-height: 1.5;
            font-family: ui-monospace, "SF Mono", monospace;
        }

        footer {
            padding-top: 2rem;
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
        }

        .footer-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
        }

        .footer-nav a {
            color: var(--text-muted);
            text-decoration: none;
        }

        .footer-nav a:hover {
            color: var(--text);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link"><?= htmlspecialchars($t['skip_to_content'], ENT_QUOTES, 'UTF-8') ?></a>

    <div class="container">
        <!-- Header -->
        <header>
            <div class="brand">
                <a href="?locale=<?= urlencode($locale) ?>">example-dns</a>
            </div>

            <div class="header-nav">
                <a href="https://admin.example-dns.com" target="_blank" rel="noopener" class="nav-admin-btn"><?= htmlspecialchars($t['nav_admin'], ENT_QUOTES, 'UTF-8') ?> &rarr;</a>
                <nav class="lang-nav" aria-label="Language selector">
                    <a href="?locale=en"<?= $locale === 'en' ? ' aria-current="page"' : '' ?>>EN</a>
                    <a href="?locale=de"<?= $locale === 'de' ? ' aria-current="page"' : '' ?>>DE</a>
                </nav>
            </div>
        </header>

        <main id="main-content">
            <!-- Hero -->
            <section class="hero">
                <div class="status-badge">
                    <span class="pulse-dot" aria-hidden="true"></span>
                    <?= htmlspecialchars($t['badge_status'], ENT_QUOTES, 'UTF-8') ?>
                </div>

                <h1><?= htmlspecialchars($t['hero_title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="hero-desc"><?= htmlspecialchars($t['hero_description'], ENT_QUOTES, 'UTF-8') ?></p>

                <div class="hero-actions">
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

                <p class="hero-attribution"><?= $t['project_by'] ?></p>
            </section>

            <!-- Delegation -->
            <section class="content-section">
                <h2 class="section-title"><?= htmlspecialchars($t['delegation_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="section-desc"><?= htmlspecialchars($t['delegation_desc'], ENT_QUOTES, 'UTF-8') ?></p>

                <div class="code-box">
                    <div class="code-header">
                        <span>NAMESERVERS</span>
                        <button type="button" class="copy-btn" id="copy-ns-btn"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <pre><code id="ns-code">example-dns.net
example-dns.org</code></pre>
                </div>
            </section>

            <!-- Nodes -->
            <section class="content-section">
                <h2 class="section-title"><?= htmlspecialchars($t['nodes_title'], ENT_QUOTES, 'UTF-8') ?></h2>

                <div class="nodes-grid">
                    <article class="node-card">
                        <span class="node-role"><?= htmlspecialchars($t['node_primary_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="node-host"><?= htmlspecialchars($t['node_primary_host'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="node-desc"><?= htmlspecialchars($t['node_primary_desc'], ENT_QUOTES, 'UTF-8') ?></span>
                    </article>

                    <article class="node-card">
                        <span class="node-role"><?= htmlspecialchars($t['node_secondary_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="node-host"><?= htmlspecialchars($t['node_secondary_host'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="node-desc"><?= htmlspecialchars($t['node_secondary_desc'], ENT_QUOTES, 'UTF-8') ?></span>
                    </article>

                    <article class="node-card">
                        <span class="node-role"><?= htmlspecialchars($t['node_web_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="node-host"><?= htmlspecialchars($t['node_web_host'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="node-desc"><?= htmlspecialchars($t['node_web_desc'], ENT_QUOTES, 'UTF-8') ?></span>
                    </article>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer>
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

    <script>
    (function () {
        var copyBtn = document.getElementById('copy-ns-btn');
        var nsCode = document.getElementById('ns-code');
        var copiedLabel = <?= json_encode($t['copied_label'], JSON_UNESCAPED_UNICODE) ?>;
        var copyLabel = <?= json_encode($t['copy_label'], JSON_UNESCAPED_UNICODE) ?>;

        if (copyBtn && nsCode) {
            copyBtn.addEventListener('click', function () {
                var text = nsCode.textContent.trim();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); } catch (e) {}
                    document.body.removeChild(ta);
                }
                copyBtn.textContent = copiedLabel;
                setTimeout(function () { copyBtn.textContent = copyLabel; }, 1500);
            });
        }
    })();
    </script>
</body>
</html>
