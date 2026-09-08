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
</head>
<body>
    <header>
        <p><strong><a href="?locale=<?= urlencode($locale) ?>">example-dns</a></strong></p>
        <nav aria-label="Language selector">
            <a href="?locale=en">EN</a> | <a href="?locale=de">DE</a>
        </nav>
    </header>
    <hr>

    <main>
        <section>
            <h1><?= htmlspecialchars($t['hero_heading'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p><small><?= htmlspecialchars($t['badge_open_source'], ENT_QUOTES, 'UTF-8') ?></small></p>
            <p><?= htmlspecialchars($t['hero_description'], ENT_QUOTES, 'UTF-8') ?></p>
            <p>
                <a href="https://github.com/example-dns/example-dns" target="_blank" rel="noopener"><?= htmlspecialchars($t['view_on_github'], ENT_QUOTES, 'UTF-8') ?></a> |
                <a href="https://codeberg.org/example-dns/example-dns" target="_blank" rel="noopener"><?= htmlspecialchars($t['view_on_codeberg'], ENT_QUOTES, 'UTF-8') ?></a>
            </p>
            <p><small><?= $t['project_by'] ?></small></p>
        </section>
        <hr>

        <section>
            <h2><?= htmlspecialchars($t['infrastructure_title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <ul>
                <li>
                    <strong><?= htmlspecialchars($t['domain_web_title'], ENT_QUOTES, 'UTF-8') ?></strong> &mdash;
                    <?= htmlspecialchars($t['domain_web_desc'], ENT_QUOTES, 'UTF-8') ?>
                </li>
                <li>
                    <strong><?= htmlspecialchars($t['domain_primary_title'], ENT_QUOTES, 'UTF-8') ?></strong> &mdash;
                    <?= htmlspecialchars($t['domain_primary_desc'], ENT_QUOTES, 'UTF-8') ?>
                </li>
                <li>
                    <strong><?= htmlspecialchars($t['domain_secondary_title'], ENT_QUOTES, 'UTF-8') ?></strong> &mdash;
                    <?= htmlspecialchars($t['domain_secondary_desc'], ENT_QUOTES, 'UTF-8') ?>
                </li>
            </ul>
        </section>
        <hr>

        <section>
            <h2><?= htmlspecialchars($t['features_title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <ul>
                <li>
                    <strong><?= htmlspecialchars($t['feature_perf_title'], ENT_QUOTES, 'UTF-8') ?></strong>:
                    <?= htmlspecialchars($t['feature_perf_desc'], ENT_QUOTES, 'UTF-8') ?>
                </li>
                <li>
                    <strong><?= htmlspecialchars($t['feature_oss_title'], ENT_QUOTES, 'UTF-8') ?></strong>:
                    <?= htmlspecialchars($t['feature_oss_desc'], ENT_QUOTES, 'UTF-8') ?>
                </li>
                <li>
                    <strong><?= htmlspecialchars($t['feature_standards_title'], ENT_QUOTES, 'UTF-8') ?></strong>:
                    <?= htmlspecialchars($t['feature_standards_desc'], ENT_QUOTES, 'UTF-8') ?>
                </li>
            </ul>
        </section>
    </main>
    <hr>

    <footer>
        <p><?= $t['footer_text'] ?></p>
        <nav>
            <a href="<?= htmlspecialchars($imprint_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($t['imprint'], ENT_QUOTES, 'UTF-8') ?></a> |
            <a href="https://github.com/example-dns/example-dns" target="_blank" rel="noopener">GitHub</a> |
            <a href="https://codeberg.org/example-dns/example-dns" target="_blank" rel="noopener">Codeberg</a> |
            <a href="https://ternis.org" target="_blank" rel="noopener">ternis.org</a>
        </nav>
    </footer>
</body>
</html>
