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
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($t['tagline'], ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container nav-wrapper">
            <a href="?locale=<?= urlencode($locale) ?>" class="brand">
                <span class="brand-dot"></span>
                example-dns
            </a>
            <nav class="lang-switch" aria-label="Language selector">
                <a href="?locale=en" class="lang-btn <?= $locale === 'en' ? 'active' : '' ?>">EN</a>
                <a href="?locale=de" class="lang-btn <?= $locale === 'de' ? 'active' : '' ?>">DE</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <div class="badge">
                    <span>⚡</span>
                    <?= htmlspecialchars($t['badge_open_source'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <h1><?= htmlspecialchars($t['hero_heading'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars($t['hero_description'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="hero-actions">
                    <a href="https://github.com/example-dns/example-dns" target="_blank" rel="noopener" class="btn btn-primary">
                        <?= htmlspecialchars($t['view_on_github'], ENT_QUOTES, 'UTF-8') ?> &rarr;
                    </a>
                    <a href="https://codeberg.org/example-dns/example-dns" target="_blank" rel="noopener" class="btn btn-primary">
                        <?= htmlspecialchars($t['view_on_codeberg'], ENT_QUOTES, 'UTF-8') ?> &rarr;
                    </a>
                </div>
                <div class="hero-subtext">
                    <?= $t['project_by'] ?>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <h2 class="section-title"><?= htmlspecialchars($t['infrastructure_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="grid-3">
                    <article class="card">
                        <div class="card-tag">Portal &amp; UI</div>
                        <h3><?= htmlspecialchars($t['domain_web_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($t['domain_web_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                    <article class="card">
                        <div class="card-tag">Primary NS</div>
                        <h3><?= htmlspecialchars($t['domain_primary_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($t['domain_primary_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                    <article class="card">
                        <div class="card-tag">Secondary NS</div>
                        <h3><?= htmlspecialchars($t['domain_secondary_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($t['domain_secondary_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <h2 class="section-title"><?= htmlspecialchars($t['features_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="grid-3">
                    <article class="card">
                        <h3><?= htmlspecialchars($t['feature_perf_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($t['feature_perf_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                    <article class="card">
                        <h3><?= htmlspecialchars($t['feature_oss_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($t['feature_oss_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                    <article class="card">
                        <h3><?= htmlspecialchars($t['feature_standards_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($t['feature_standards_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-content">
            <p><?= $t['footer_text'] ?></p>
            <ul class="footer-links">
                <li><a href="<?= htmlspecialchars($imprint_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($t['imprint'], ENT_QUOTES, 'UTF-8') ?></a></li>
                <li><a href="https://github.com/example-dns/example-dns" target="_blank" rel="noopener">GitHub</a></li>
                <li><a href="https://codeberg.org/example-dns/example-dns" target="_blank" rel="noopener">Codeberg</a></li>
                <li><a href="https://ternis.org" target="_blank" rel="noopener">ternis.org</a></li>
            </ul>
        </div>
    </footer>
</body>
</html>
