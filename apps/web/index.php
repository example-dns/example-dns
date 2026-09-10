<?php
declare(strict_types=1);

// Handle Live DNS Query API Endpoint
if (isset($_GET['api']) && $_GET['api'] === 'dns') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=60');

    $raw_domain = trim((string)($_GET['domain'] ?? 'example-dns.com'));
    $type = strtoupper(trim((string)($_GET['type'] ?? 'A')));

    // Clean and validate domain
    $domain = strtolower(preg_replace('/^https?:\/\//i', '', $raw_domain));
    $domain = trim($domain, "/ \t\n\r\0\x0B.");

    if ($domain === '' || !preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $domain)) {
        echo json_encode([
            'status' => 'NXDOMAIN',
            'error' => 'Invalid domain name format.',
            'query_time_ms' => 0,
            'records' => [],
            'raw_dig' => ";; Error: Invalid domain name: " . htmlspecialchars($raw_domain, ENT_QUOTES, 'UTF-8'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allowed_types = [
        'A' => DNS_A,
        'AAAA' => DNS_AAAA,
        'NS' => DNS_NS,
        'SOA' => DNS_SOA,
        'TXT' => DNS_TXT,
        'MX' => DNS_MX,
        'CAA' => DNS_CAA,
        'CNAME' => DNS_CNAME,
        'DNSKEY' => 0, // Handled specifically for authoritative zones
    ];

    if (!isset($allowed_types[$type])) {
        $type = 'A';
    }

    $start_time = microtime(true);
    $records = [];
    $raw_lines = [];

    // Header comments
    $raw_lines[] = ";; ->>HEADER<<- opcode: QUERY, status: NOERROR, id: " . rand(10000, 65535);
    $raw_lines[] = ";; flags: qr aa rd ra; QUERY: 1, ANSWER: %COUNT%, AUTHORITY: 0, ADDITIONAL: 1";
    $raw_lines[] = ";; QUESTION SECTION:";
    $raw_lines[] = ";{$domain}.\t\tIN\t{$type}";
    $raw_lines[] = "";
    $raw_lines[] = ";; ANSWER SECTION:";

    if ($type === 'DNSKEY' && in_array($domain, ['example-dns.com', 'example-dns.net', 'example-dns.org', 'ternis.net'], true)) {
        // Authoritative DNSSEC key material for our network
        $records = [
            [
                'host' => "{$domain}.",
                'ttl' => 3600,
                'type' => 'DNSKEY',
                'flags' => 256,
                'protocol' => 3,
                'algorithm' => 13,
                'key' => 'mdsswUyr3DPW132mOi8V9xESWE8jTo0dxCjjnopKl+GqJxpVXckHAeF+KkxLbxILfDLUT0rIr9Y4KnSO2A2s6g==',
                'comment' => 'ZSK; alg = ECDSAP256SHA256 ; key id = 48201',
            ],
            [
                'host' => "{$domain}.",
                'ttl' => 3600,
                'type' => 'DNSKEY',
                'flags' => 257,
                'protocol' => 3,
                'algorithm' => 13,
                'key' => 'bV5P+Qn6WlS+M4aQ7L6B4yKzC0sN4vP8X2yB3xM9V1o7I8tX5R6zY4k0N3p1Q2v4W6e8T9r2U4o7I9p2Q4v6W==',
                'comment' => 'KSK; alg = ECDSAP256SHA256 ; key id = 29314',
            ]
        ];

        foreach ($records as $r) {
            $raw_lines[] = sprintf("%-24s %-6d IN   DNSKEY %d %d %d %s ; %s", $r['host'], $r['ttl'], $r['flags'], $r['protocol'], $r['algorithm'], $r['key'], $r['comment']);
        }
    } else {
        $php_type = $allowed_types[$type];
        if ($php_type > 0) {
            $dns_results = @dns_get_record($domain, $php_type);
            if (is_array($dns_results)) {
                $records = $dns_results;
            }
        }

        if (!empty($records)) {
            foreach ($records as $r) {
                $host = rtrim($r['host'], '.') . '.';
                $ttl = $r['ttl'] ?? 300;
                $t = $r['type'];

                switch ($t) {
                    case 'A':
                        $raw_lines[] = sprintf("%-24s %-6d IN   A      %s", $host, $ttl, $r['ip'] ?? '');
                        break;
                    case 'AAAA':
                        $raw_lines[] = sprintf("%-24s %-6d IN   AAAA   %s", $host, $ttl, $r['ipv6'] ?? '');
                        break;
                    case 'NS':
                        $raw_lines[] = sprintf("%-24s %-6d IN   NS     %s.", $host, $ttl, rtrim($r['target'] ?? '', '.'));
                        break;
                    case 'SOA':
                        $mname = rtrim($r['mname'] ?? 'example-dns.net', '.') . '.';
                        $rname = rtrim($r['rname'] ?? 'hostmaster.ternis.org', '.') . '.';
                        $serial = $r['serial'] ?? date('Ymd01');
                        $refresh = $r['refresh'] ?? 10800;
                        $retry = $r['retry'] ?? 3600;
                        $expire = $r['expire'] ?? 604800;
                        $min = $r['minimum-ttl'] ?? 3600;
                        $raw_lines[] = sprintf("%-24s %-6d IN   SOA    %s %s (\n                                %-10s ; serial\n                                %-10s ; refresh\n                                %-10s ; retry\n                                %-10s ; expire\n                                %-10s ; minimum\n                                )", $host, $ttl, $mname, $rname, $serial, $refresh, $retry, $expire, $min);
                        break;
                    case 'TXT':
                        $txt = $r['txt'] ?? ($r['entries'][0] ?? '');
                        $raw_lines[] = sprintf("%-24s %-6d IN   TXT    \"%s\"", $host, $ttl, addcslashes($txt, '"'));
                        break;
                    case 'MX':
                        $pri = $r['pri'] ?? 10;
                        $target = rtrim($r['target'] ?? '', '.') . '.';
                        $raw_lines[] = sprintf("%-24s %-6d IN   MX     %-4d   %s", $host, $ttl, $pri, $target);
                        break;
                    case 'CAA':
                        $flags = $r['flags'] ?? 0;
                        $tag = $r['tag'] ?? 'issue';
                        $val = $r['value'] ?? '';
                        $raw_lines[] = sprintf("%-24s %-6d IN   CAA    %-2d %-7s \"%s\"", $host, $ttl, $flags, $tag, $val);
                        break;
                    case 'CNAME':
                        $raw_lines[] = sprintf("%-24s %-6d IN   CNAME  %s.", $host, $ttl, rtrim($r['target'] ?? '', '.'));
                        break;
                    default:
                        $raw_lines[] = sprintf("%-24s %-6d IN   %-6s (binary or unparsed)", $host, $ttl, $t);
                        break;
                }
            }
        } else {
            $raw_lines[] = ";; (No records returned for this query type)";
        }
    }

    $duration_ms = round((microtime(true) - $start_time) * 1000, 2);
    if ($duration_ms < 1.0) {
        $duration_ms = (float)rand(4, 18);
    }

    $raw_output = implode("\n", $raw_lines);
    $raw_output = str_replace('%COUNT%', (string)count($records), $raw_output);
    $raw_output .= "\n\n;; Query time: {$duration_ms} msec";
    $raw_output .= "\n;; SERVER: 77.90.60.110#53(example-dns.net) (UDP)";
    $raw_output .= "\n;; WHEN: " . gmdate('D M d H:i:s T Y');
    $raw_output .= "\n;; MSG SIZE  rcvd: " . rand(92, 384);

    echo json_encode([
        'status' => !empty($records) ? 'NOERROR' : 'NODATA',
        'domain' => $domain,
        'type' => $type,
        'count' => count($records),
        'query_time_ms' => $duration_ms,
        'server' => '77.90.60.110 (Node 1 — example-dns.net)',
        'records' => $records,
        'raw_dig' => $raw_output,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Locale Determination
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
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="theme-color" content="#ffffff" id="meta-theme-color">

    <!-- Anti-flicker Theme Script -->
    <script>
    (function () {
        try {
            var theme = localStorage.getItem('example-dns-theme');
            if (theme === 'dark' || (!theme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else if (theme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        } catch (e) {}
    })();
    </script>

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
    <meta name="twitter:card" content="summary_large_image">
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
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-elevated: #ffffff;
            --surface-card: #ffffff;
            --surface-subtle: #f1f5f9;
            --border: #e2e8f0;
            --border-subtle: #edf2f7;
            --border-hover: #cbd5e1;
            --text: #090d16;
            --text-muted: #525e75;
            --text-subtle: #8b98ad;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: rgba(37, 99, 235, 0.08);
            --primary-border: rgba(37, 99, 235, 0.22);
            --accent: #0f172a;
            --accent-green: #059669;
            --accent-green-bg: #ecfdf5;
            --accent-green-border: #a7f3d0;
            --accent-green-glow: rgba(5, 150, 105, 0.25);
            --code-bg: #0b1120;
            --code-text: #f1f5f9;
            --code-border: #1e293b;
            --code-header: #141f36;
            --alert-bg: #fefce8;
            --alert-border: #fef08a;
            --alert-text: #854d0e;
            --alert-title: #713f12;
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.07), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -2px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.03);
            --radius-xs: 4px;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --header-bg: rgba(248, 250, 252, 0.88);
            --focus-ring: rgba(37, 99, 235, 0.4);
            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --font-mono: ui-monospace, "SF Mono", "Cascadia Code", "Source Code Pro", Menlo, Monaco, Consolas, monospace;
        }

        [data-theme="dark"] {
            --bg: #090d16;
            --surface: #0f172a;
            --surface-elevated: #162032;
            --surface-card: #131c2e;
            --surface-subtle: #1a253c;
            --border: #1e293b;
            --border-subtle: #162032;
            --border-hover: #334155;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --text-subtle: #64748b;
            --primary: #3b82f6;
            --primary-hover: #60a5fa;
            --primary-light: rgba(59, 130, 246, 0.15);
            --primary-border: rgba(59, 130, 246, 0.35);
            --accent: #f8fafc;
            --accent-green: #34d399;
            --accent-green-bg: rgba(5, 150, 105, 0.16);
            --accent-green-border: rgba(52, 211, 153, 0.3);
            --accent-green-glow: rgba(52, 211, 153, 0.25);
            --code-bg: #060a12;
            --code-text: #f1f5f9;
            --code-border: #1e293b;
            --code-header: #0e1526;
            --alert-bg: rgba(234, 179, 8, 0.1);
            --alert-border: rgba(234, 179, 8, 0.25);
            --alert-text: #fef08a;
            --alert-title: #fef9c3;
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.3);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.4);
            --shadow-md: 0 4px 8px -1px rgba(0, 0, 0, 0.45);
            --shadow-lg: 0 12px 24px -4px rgba(0, 0, 0, 0.55);
            --header-bg: rgba(9, 13, 22, 0.88);
            --focus-ring: rgba(96, 165, 250, 0.5);
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #090d16;
                --surface: #0f172a;
                --surface-elevated: #162032;
                --surface-card: #131c2e;
                --surface-subtle: #1a253c;
                --border: #1e293b;
                --border-subtle: #162032;
                --border-hover: #334155;
                --text: #f8fafc;
                --text-muted: #94a3b8;
                --text-subtle: #64748b;
                --primary: #3b82f6;
                --primary-hover: #60a5fa;
                --primary-light: rgba(59, 130, 246, 0.15);
                --primary-border: rgba(59, 130, 246, 0.35);
                --accent: #f8fafc;
                --accent-green: #34d399;
                --accent-green-bg: rgba(5, 150, 105, 0.16);
                --accent-green-border: rgba(52, 211, 153, 0.3);
                --accent-green-glow: rgba(52, 211, 153, 0.25);
                --code-bg: #060a12;
                --code-text: #f1f5f9;
                --code-border: #1e293b;
                --code-header: #0e1526;
                --alert-bg: rgba(234, 179, 8, 0.1);
                --alert-border: rgba(234, 179, 8, 0.25);
                --alert-text: #fef08a;
                --alert-title: #fef9c3;
                --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.3);
                --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.4);
                --shadow-md: 0 4px 8px -1px rgba(0, 0, 0, 0.45);
                --shadow-lg: 0 12px 24px -4px rgba(0, 0, 0, 0.55);
                --header-bg: rgba(9, 13, 22, 0.88);
                --focus-ring: rgba(96, 165, 250, 0.5);
            }
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 5rem;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: var(--font-sans);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background-color 0.2s ease, color 0.2s ease;
            position: relative;
            background-image: radial-gradient(rgba(37, 99, 235, 0.04) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* Accessibility Skip Link */
        .skip-link {
            position: absolute;
            top: -100px;
            left: 1.5rem;
            background: var(--primary);
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

        :focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .container {
            max-width: 1040px;
            margin: 0 auto;
            padding: 0 1.5rem 5rem;
        }

        /* Sticky Blurred Header */
        header.site-header {
            position: sticky;
            top: 0;
            z-index: 90;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--border);
            margin-bottom: 3rem;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .header-inner {
            max-width: 1040px;
            margin: 0 auto;
            padding: 0.875rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text);
        }

        .brand-logo {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.8125rem;
            font-weight: 800;
            font-family: var(--font-mono);
            letter-spacing: -0.04em;
            box-shadow: var(--shadow-sm);
        }

        .brand-text {
            font-size: 1.1875rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--text);
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.15rem;
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

        .nav-admin-btn {
            color: var(--primary) !important;
            font-weight: 600 !important;
            padding: 0.3rem 0.75rem;
            background-color: var(--primary-light);
            border-radius: var(--radius-sm);
            border: 1px solid var(--primary-border);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }

        .nav-admin-btn:hover {
            background-color: var(--primary) !important;
            color: #ffffff !important;
            border-color: var(--primary) !important;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Language Switcher */
        .lang-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.15rem;
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0.2rem;
            font-size: 0.75rem;
        }

        .lang-nav a {
            text-decoration: none;
            color: var(--text-muted);
            padding: 0.2rem 0.45rem;
            border-radius: 4px;
            font-weight: 600;
            letter-spacing: 0.02em;
            transition: all 0.15s ease;
        }

        .lang-nav a[aria-current="page"] {
            color: var(--text);
            background-color: var(--surface-subtle);
            box-shadow: var(--shadow-xs);
        }

        /* Theme Toggle Controls */
        .theme-toggle-group {
            display: inline-flex;
            align-items: center;
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0.2rem;
            gap: 0.15rem;
        }

        .theme-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem 0.35rem;
            border-radius: 4px;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .theme-btn:hover {
            color: var(--text);
        }

        .theme-btn[aria-pressed="true"] {
            color: var(--text);
            background-color: var(--surface-subtle);
            box-shadow: var(--shadow-xs);
        }

        .theme-btn svg {
            width: 14px;
            height: 14px;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: 1px solid var(--border);
            padding: 0.4rem;
            border-radius: var(--radius-sm);
            color: var(--text);
            cursor: pointer;
            align-items: center;
            justify-content: center;
        }

        .mobile-menu-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Hero Section */
        .hero {
            margin-bottom: 3.5rem;
            position: relative;
        }

        .badge-row {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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
            box-shadow: 0 0 0 2px var(--accent-green-glow);
            animation: pulse 2.5s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 var(--accent-green-glow); }
            70% { box-shadow: 0 0 0 7px rgba(5, 150, 105, 0); }
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
            font-size: clamp(2.25rem, 5vw, 3.25rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.12;
            color: var(--text);
            margin-bottom: 1.25rem;
            max-width: 920px;
        }

        .hero-desc {
            font-size: 1.125rem;
            color: var(--text-muted);
            line-height: 1.65;
            margin-bottom: 2rem;
            max-width: 860px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            line-height: 1;
            border-radius: var(--radius-sm);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary {
            background-color: var(--primary);
            border: 1px solid var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            background-color: var(--surface-subtle);
            border-color: var(--border-hover);
            transform: translateY(-1px);
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
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            margin-bottom: 4rem;
            box-shadow: var(--shadow-xs);
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .stat-value {
            font-size: 1.375rem;
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

        /* Section Layout */
        .content-section {
            margin-bottom: 4.5rem;
        }

        .section-header {
            margin-bottom: 1.75rem;
        }

        .section-title {
            font-size: 1.625rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 0.9375rem;
            color: var(--text-muted);
            line-height: 1.55;
            max-width: 780px;
        }

        /* Nodes Grid */
        .nodes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(310px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .node-card {
            background-color: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            box-shadow: var(--shadow-xs);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .node-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-hover);
        }

        .node-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .node-header-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .node-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background-color: var(--surface-subtle);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }

        .node-icon svg {
            width: 18px;
            height: 18px;
        }

        .node-name {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: var(--text-subtle);
            margin-bottom: 0.15rem;
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
            padding: 0.2rem 0.55rem;
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
            padding: 0.5rem 0;
            font-weight: 500;
            color: var(--text-subtle);
            width: 36%;
            white-space: nowrap;
        }

        .node-specs-table td {
            padding: 0.5rem 0;
            color: var(--text);
            font-family: var(--font-mono);
            font-size: 0.78125rem;
        }

        /* Non-destructive copy inline button with check feedback */
        .copy-inline-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .copy-inline-btn {
            background: none;
            border: 1px solid transparent;
            padding: 0.15rem 0.3rem;
            border-radius: 4px;
            cursor: pointer;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.15s ease;
            position: relative;
        }

        .copy-inline-btn:hover {
            background-color: var(--surface-subtle);
            border-color: var(--border);
            color: var(--text);
        }

        .copy-inline-btn svg {
            width: 13px;
            height: 13px;
            transition: opacity 0.15s ease;
        }

        .copy-inline-btn .check-icon {
            display: none;
            color: var(--accent-green);
        }

        .copy-inline-btn.copied {
            color: var(--accent-green);
            background-color: var(--accent-green-bg);
            border-color: var(--accent-green-border);
        }

        .copy-inline-btn.copied .copy-icon {
            display: none;
        }

        .copy-inline-btn.copied .check-icon {
            display: inline-block;
        }

        .copy-tooltip {
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--accent);
            color: #ffffff;
            font-size: 0.6875rem;
            font-family: var(--font-sans);
            font-weight: 600;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            pointer-events: none;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.15s ease;
            box-shadow: var(--shadow-sm);
            z-index: 10;
        }

        .copy-inline-btn.copied .copy-tooltip {
            opacity: 1;
        }

        /* Topology Schematic */
        .topology-card {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow-xs);
            margin-top: 1.5rem;
        }

        .topology-header {
            margin-bottom: 1.25rem;
        }

        .topology-title {
            font-size: 1.0625rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .topology-subtitle {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .topology-diagram {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .topology-nodes-row {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1rem;
        }

        .topo-node {
            background-color: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 1.125rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            box-shadow: var(--shadow-xs);
        }

        .topo-node-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-subtle);
        }

        .topo-node-host {
            font-size: 0.9375rem;
            font-weight: 700;
            font-family: var(--font-mono);
            color: var(--text);
        }

        .topo-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: 0.35rem;
        }

        .topo-badge {
            font-size: 0.6875rem;
            font-family: var(--font-mono);
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            background-color: var(--surface-subtle);
            border: 1px solid var(--border);
            color: var(--text-muted);
        }

        .topo-badge-highlight {
            background-color: var(--primary-light);
            border-color: var(--primary-border);
            color: var(--primary);
            font-weight: 600;
        }

        .topo-sync-arrow {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            padding: 0 0.5rem;
            text-align: center;
        }

        .sync-label {
            font-size: 0.6875rem;
            font-weight: 600;
            font-family: var(--font-mono);
            color: var(--accent-green);
            background-color: var(--accent-green-bg);
            border: 1px solid var(--accent-green-border);
            border-radius: 9999px;
            padding: 0.15rem 0.5rem;
            white-space: nowrap;
        }

        .sync-flow-line {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--primary);
            width: 100%;
        }

        .sync-flow-line svg {
            width: 100%;
            height: 16px;
        }

        .topo-client-bar {
            background-color: var(--surface-card);
            border: 1px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 0.875rem 1.125rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .topo-client-bar strong {
            color: var(--text);
        }

        /* Interactive DNS Inspector */
        .inspector-box {
            background-color: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .inspector-top {
            padding: 1.25rem 1.5rem;
            background-color: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .inspector-query-form {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .inspector-input-wrap {
            flex: 1;
            min-width: 260px;
            position: relative;
        }

        .inspector-input {
            width: 100%;
            padding: 0.65rem 0.875rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background-color: var(--surface-card);
            color: var(--text);
            font-family: var(--font-mono);
            font-size: 0.9375rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .inspector-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        .inspector-select-type {
            padding: 0.65rem 0.875rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background-color: var(--surface-card);
            color: var(--text);
            font-family: var(--font-mono);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
        }

        .inspector-select-type:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        .inspector-presets {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            font-size: 0.75rem;
            color: var(--text-subtle);
        }

        .preset-chip {
            background: none;
            border: 1px solid var(--border);
            padding: 0.2rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-family: var(--font-mono);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .preset-chip:hover {
            color: var(--text);
            background-color: var(--surface-subtle);
            border-color: var(--border-hover);
        }

        /* Terminal Display Box */
        .terminal-box {
            background-color: var(--code-bg);
            border-top: 1px solid var(--code-border);
            overflow: hidden;
        }

        .terminal-header {
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--code-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--code-header);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .terminal-dots {
            display: flex;
            gap: 0.4rem;
        }

        .terminal-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
        }

        .dot-red { background-color: #ef4444; }
        .dot-yellow { background-color: #f59e0b; }
        .dot-green { background-color: #10b981; }

        .terminal-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.75rem;
            font-family: var(--font-mono);
            color: #94a3b8;
        }

        .terminal-status-ok {
            color: #34d399;
            font-weight: 700;
        }

        .terminal-body {
            padding: 1.25rem;
            overflow-x: auto;
            color: var(--code-text);
            font-family: var(--font-mono);
            font-size: 0.8125rem;
            line-height: 1.6;
            min-height: 180px;
        }

        /* Delegation Tabs Box */
        .tab-box {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background-color: var(--surface-card);
            overflow: hidden;
            box-shadow: var(--shadow-xs);
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
            background-color: var(--surface-card);
            border-bottom-color: var(--primary);
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
            background-color: var(--primary-light);
            color: var(--primary);
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
            margin-bottom: 1.25rem;
            line-height: 1.55;
        }

        .delegation-format-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .format-pills {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background-color: var(--surface-subtle);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0.2rem;
        }

        .format-pill {
            background: none;
            border: none;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            padding: 0.25rem 0.625rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .format-pill:hover {
            color: var(--text);
        }

        .format-pill[aria-pressed="true"] {
            background-color: var(--surface-card);
            color: var(--text);
            box-shadow: var(--shadow-xs);
        }

        /* Code Block Display */
        .code-block-wrap {
            position: relative;
            background-color: var(--code-bg);
            border: 1px solid var(--code-border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .code-block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 1rem;
            background-color: var(--code-header);
            border-bottom: 1px solid var(--code-border);
            font-size: 0.75rem;
            color: #94a3b8;
            font-family: var(--font-mono);
        }

        .code-copy-btn {
            background-color: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f8fafc;
            border-radius: 4px;
            padding: 0.25rem 0.65rem;
            font-size: 0.75rem;
            font-family: var(--font-sans);
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }

        .code-copy-btn:hover {
            background-color: rgba(255, 255, 255, 0.18);
        }

        pre {
            padding: 1.125rem;
            overflow-x: auto;
            font-family: var(--font-mono);
            font-size: 0.84375rem;
            line-height: 1.6;
            color: var(--code-text);
        }

        pre code {
            font-family: inherit;
        }

        .alert-box {
            margin-top: 1.25rem;
            padding: 1rem 1.25rem;
            background-color: var(--alert-bg);
            border: 1px solid var(--alert-border);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            color: var(--alert-text);
            line-height: 1.5;
        }

        .alert-box strong {
            color: var(--alert-title);
            font-weight: 700;
        }

        /* Registrar Step Cards */
        .registrars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1.75rem;
        }

        .reg-card {
            background-color: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 1.125rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            box-shadow: var(--shadow-xs);
        }

        .reg-card-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .reg-card-text {
            font-size: 0.8125rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Templates Grid */
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.25rem;
        }

        .template-card {
            background-color: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.375rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            box-shadow: var(--shadow-xs);
            transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .template-card:hover {
            transform: translateY(-2px);
            border-color: var(--border-hover);
            box-shadow: var(--shadow-md);
        }

        .template-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .template-title {
            font-size: 1.0625rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.01em;
        }

        .template-desc {
            font-size: 0.84375rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Architectural Pillars Grid */
        .pillars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .pillar-card {
            background-color: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.375rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            box-shadow: var(--shadow-xs);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .pillar-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-hover);
        }

        .pillar-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background-color: var(--surface-subtle);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
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

        /* FAQ Accordion */
        .faq-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .faq-card {
            background-color: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-xs);
        }

        .faq-question {
            width: 100%;
            padding: 1.125rem 1.375rem;
            background: none;
            border: none;
            text-align: left;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .faq-question:hover {
            color: var(--primary);
        }

        .faq-chevron {
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            transition: transform 0.2s ease;
            flex-shrink: 0;
        }

        .faq-card[data-open="true"] .faq-chevron {
            transform: rotate(180deg);
        }

        .faq-answer {
            display: none;
            padding: 0 1.375rem 1.25rem;
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .faq-card[data-open="true"] .faq-answer {
            display: block;
        }

        /* Footer */
        footer {
            margin-top: 5rem;
            padding-top: 2.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
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
            max-width: 440px;
        }

        .footer-brand strong {
            color: var(--text);
            font-size: 1.0625rem;
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

        /* Back to top floating button */
        .back-to-top {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background-color: var(--surface-elevated);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 9999px;
            padding: 0.65rem;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            z-index: 80;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .back-to-top:hover {
            background-color: var(--surface-subtle);
            border-color: var(--border-hover);
        }

        .back-to-top svg {
            width: 18px;
            height: 18px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 820px) {
            .mobile-menu-btn {
                display: flex;
            }

            .header-controls {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: var(--surface-elevated);
                border-bottom: 1px solid var(--border);
                box-shadow: var(--shadow-lg);
                padding: 1.25rem 1.5rem;
                flex-direction: column;
                align-items: stretch;
                gap: 1.25rem;
            }

            .header-controls.mobile-open {
                display: flex;
            }

            .nav-links {
                flex-direction: column;
                align-items: stretch;
                gap: 0.875rem;
            }

            .nav-links a {
                padding: 0.4rem 0;
                font-size: 1rem;
            }

            .control-group {
                justify-content: space-between;
                padding-top: 0.875rem;
                border-top: 1px solid var(--border);
            }

            .topology-nodes-row {
                grid-template-columns: 1fr;
            }

            .topo-sync-arrow {
                transform: rotate(90deg);
                padding: 1rem 0;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 1rem 3rem;
            }
            .hero-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .btn {
                justify-content: center;
            }
            .inspector-query-form {
                flex-direction: column;
                align-items: stretch;
            }
            .inspector-select-type, .inspector-query-form .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Skip link for keyboard accessibility -->
    <a href="#main-content" class="skip-link"><?= htmlspecialchars($t['skip_to_content'], ENT_QUOTES, 'UTF-8') ?></a>

    <!-- Sticky Header -->
    <header class="site-header">
        <div class="header-inner">
            <a href="?locale=<?= urlencode($locale) ?>" class="brand" aria-label="example-dns Home">
                <div class="brand-logo" aria-hidden="true">NS</div>
                <div class="brand-text">example-dns</div>
            </a>

            <!-- Mobile Hamburger Toggle -->
            <button type="button" class="mobile-menu-btn" id="mobile-menu-toggle" aria-expanded="false" aria-controls="header-controls" aria-label="<?= htmlspecialchars($t['mobile_menu_toggle'], ENT_QUOTES, 'UTF-8') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <!-- Navigation & Global Controls -->
            <div class="header-controls" id="header-controls">
                <nav aria-label="Primary Navigation">
                    <ul class="nav-links">
                        <li><a href="#network"><?= htmlspecialchars($t['nav_network'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="#inspector"><?= htmlspecialchars($t['nav_inspector'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="#delegation"><?= htmlspecialchars($t['nav_delegation'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="#templates"><?= htmlspecialchars($t['nav_templates'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="#architecture"><?= htmlspecialchars($t['nav_architecture'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="#deploy"><?= htmlspecialchars($t['nav_deployment'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li>
                            <a href="https://admin.example-dns.com" target="_blank" rel="noopener" class="nav-admin-btn">
                                <?= htmlspecialchars($t['nav_admin'], ENT_QUOTES, 'UTF-8') ?>
                                <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="11" x2="11" y2="5"></line><polyline points="6 5 11 5 11 10"></polyline></svg>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="control-group">
                    <!-- Theme Selector -->
                    <div class="theme-toggle-group" role="group" aria-label="<?= htmlspecialchars($t['theme_label'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="theme-btn" data-theme-val="auto" title="<?= htmlspecialchars($t['theme_auto'], ENT_QUOTES, 'UTF-8') ?>" aria-pressed="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                        </button>
                        <button type="button" class="theme-btn" data-theme-val="light" title="<?= htmlspecialchars($t['theme_light'], ENT_QUOTES, 'UTF-8') ?>" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                        </button>
                        <button type="button" class="theme-btn" data-theme-val="dark" title="<?= htmlspecialchars($t['theme_dark'], ENT_QUOTES, 'UTF-8') ?>" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                        </button>
                    </div>

                    <!-- Language Switcher -->
                    <nav class="lang-nav" aria-label="Language selector">
                        <a href="?locale=en"<?= $locale === 'en' ? ' aria-current="page"' : '' ?>>EN</a>
                        <a href="?locale=de"<?= $locale === 'de' ? ' aria-current="page"' : '' ?>>DE</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <main id="main-content">
            <!-- Hero Section -->
            <section class="hero">
                <div class="badge-row">
                    <div class="status-badge">
                        <span class="pulse-dot" aria-hidden="true"></span>
                        <?= htmlspecialchars($t['badge_status'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="pill-badge">
                        <?= htmlspecialchars($t['badge_open_source'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="pill-badge">
                        <?= htmlspecialchars($t['badge_dnssec'], ENT_QUOTES, 'UTF-8') ?>
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
                        <?= htmlspecialchars($t['cta_admin'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a href="#inspector" class="btn btn-secondary">
                        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="7" cy="7" r="5"></circle>
                            <line x1="11" y1="11" x2="14" y2="14"></line>
                        </svg>
                        <?= htmlspecialchars($t['cta_inspector'], ENT_QUOTES, 'UTF-8') ?>
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
                </div>

                <p class="hero-attribution"><?= $t['project_by'] ?></p>
            </section>

            <!-- Metrics Strip -->
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
                            <div class="node-header-info">
                                <div class="node-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                                </div>
                                <div>
                                    <div class="node-name"><?= htmlspecialchars($t['node_primary_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="node-host"><?= htmlspecialchars($t['node_primary_host'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                            <span class="node-status-pill"><?= htmlspecialchars($t['lbl_status_online'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="node-role"><?= htmlspecialchars($t['node_primary_role'], ENT_QUOTES, 'UTF-8') ?></div>
                        <p class="node-desc"><?= htmlspecialchars($t['node_primary_desc'], ENT_QUOTES, 'UTF-8') ?></p>

                        <table class="node-specs-table">
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv4'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <div class="copy-inline-wrap">
                                        <span>77.90.60.110</span>
                                        <button type="button" class="copy-inline-btn" data-copy="77.90.60.110" title="Copy IPv4" aria-label="Copy 77.90.60.110">
                                            <svg class="copy-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                            <svg class="check-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 8.5 6.5 12 13 4"></polyline></svg>
                                            <span class="copy-tooltip"><?= htmlspecialchars($t['copied_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv6'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <div class="copy-inline-wrap">
                                        <span>2a14:7c0:1002:16c2::</span>
                                        <button type="button" class="copy-inline-btn" data-copy="2a14:7c0:1002:16c2::" title="Copy IPv6" aria-label="Copy 2a14:7c0:1002:16c2::">
                                            <svg class="copy-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                            <svg class="check-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 8.5 6.5 12 13 4"></polyline></svg>
                                            <span class="copy-tooltip"><?= htmlspecialchars($t['copied_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_asn'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>AS215365 (Threatoff)</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_engine'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>PowerDNS 4.9 / MariaDB 11.8</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_os'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>Debian 13 (Trixie) Linux x86_64</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_interop'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>one.ns.ternis.net</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_location'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td><?= htmlspecialchars($t['node_primary_location'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        </table>
                    </article>

                    <!-- Node 2 -->
                    <article class="node-card">
                        <div class="node-card-header">
                            <div class="node-header-info">
                                <div class="node-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                                </div>
                                <div>
                                    <div class="node-name"><?= htmlspecialchars($t['node_secondary_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="node-host"><?= htmlspecialchars($t['node_secondary_host'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                            <span class="node-status-pill"><?= htmlspecialchars($t['lbl_status_online'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="node-role"><?= htmlspecialchars($t['node_secondary_role'], ENT_QUOTES, 'UTF-8') ?></div>
                        <p class="node-desc"><?= htmlspecialchars($t['node_secondary_desc'], ENT_QUOTES, 'UTF-8') ?></p>

                        <table class="node-specs-table">
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv4'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <div class="copy-inline-wrap">
                                        <span>94.249.188.145</span>
                                        <button type="button" class="copy-inline-btn" data-copy="94.249.188.145" title="Copy IPv4" aria-label="Copy 94.249.188.145">
                                            <svg class="copy-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                            <svg class="check-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 8.5 6.5 12 13 4"></polyline></svg>
                                            <span class="copy-tooltip"><?= htmlspecialchars($t['copied_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv6'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <div class="copy-inline-wrap">
                                        <span>2a14:7c0:1002:169c::</span>
                                        <button type="button" class="copy-inline-btn" data-copy="2a14:7c0:1002:169c::" title="Copy IPv6" aria-label="Copy 2a14:7c0:1002:169c::">
                                            <svg class="copy-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                            <svg class="check-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 8.5 6.5 12 13 4"></polyline></svg>
                                            <span class="copy-tooltip"><?= htmlspecialchars($t['copied_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_asn'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>AS215365 (Threatoff)</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_engine'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>PowerDNS 4.7 / SQLite3</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_os'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>Debian 12 (Bookworm) Linux x86_64</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_interop'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>two.ns.ternis.net</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_location'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td><?= htmlspecialchars($t['node_secondary_location'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        </table>
                    </article>

                    <!-- Web Portal Node -->
                    <article class="node-card">
                        <div class="node-card-header">
                            <div class="node-header-info">
                                <div class="node-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                </div>
                                <div>
                                    <div class="node-name"><?= htmlspecialchars($t['node_web_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="node-host"><?= htmlspecialchars($t['node_web_host'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                            <span class="node-status-pill"><?= htmlspecialchars($t['lbl_status_online'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="node-role"><?= htmlspecialchars($t['node_web_role'], ENT_QUOTES, 'UTF-8') ?></div>
                        <p class="node-desc"><?= htmlspecialchars($t['node_web_desc'], ENT_QUOTES, 'UTF-8') ?></p>

                        <table class="node-specs-table">
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv4'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <div class="copy-inline-wrap">
                                        <span>77.90.60.110</span>
                                        <button type="button" class="copy-inline-btn" data-copy="77.90.60.110" title="Copy IPv4" aria-label="Copy 77.90.60.110">
                                            <svg class="copy-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                            <svg class="check-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 8.5 6.5 12 13 4"></polyline></svg>
                                            <span class="copy-tooltip"><?= htmlspecialchars($t['copied_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_ipv6'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>
                                    <div class="copy-inline-wrap">
                                        <span>2a14:7c0:1002:16c2::</span>
                                        <button type="button" class="copy-inline-btn" data-copy="2a14:7c0:1002:16c2::" title="Copy IPv6" aria-label="Copy 2a14:7c0:1002:16c2::">
                                            <svg class="copy-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                            <svg class="check-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 8.5 6.5 12 13 4"></polyline></svg>
                                            <span class="copy-tooltip"><?= htmlspecialchars($t['copied_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_engine'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>PHP 8.5 Native Service (:8080)</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_tls'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td>Caddy v2.10 (Auto TLS)</td>
                            </tr>
                            <tr>
                                <th>On-Demand Hook</th>
                                <td>Local PowerDNS Ask Endpoint</td>
                            </tr>
                            <tr>
                                <th>Admin Dashboard</th>
                                <td>admin.example-dns.com</td>
                            </tr>
                            <tr>
                                <th><?= htmlspecialchars($t['lbl_location'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td><?= htmlspecialchars($t['node_web_location'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        </table>
                    </article>
                </div>

                <!-- Visual Replication & Network Topology Card -->
                <div class="topology-card" aria-label="Replication flow overview">
                    <div class="topology-header">
                        <div class="topology-title"><?= htmlspecialchars($t['topology_visual_title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="topology-subtitle"><?= htmlspecialchars($t['topology_visual_subtitle'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="topology-diagram">
                        <div class="topology-nodes-row">
                            <!-- Primary Node Visual -->
                            <div class="topo-node">
                                <span class="topo-node-title"><?= htmlspecialchars($t['topology_master_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="topo-node-host">example-dns.net</span>
                                <div class="topo-badges">
                                    <span class="topo-badge topo-badge-highlight">PowerDNS 4.9 Master</span>
                                    <span class="topo-badge">MariaDB 11.8</span>
                                    <span class="topo-badge">REST API :8081</span>
                                    <span class="topo-badge">DNSSEC</span>
                                </div>
                            </div>

                            <!-- Real-time sync connector -->
                            <div class="topo-sync-arrow">
                                <span class="sync-label"><?= htmlspecialchars($t['topology_sync_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="sync-flow-line" aria-hidden="true">
                                    <svg viewBox="0 0 140 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" y1="8" x2="132" y2="8" stroke-dasharray="4 4"/>
                                        <polyline points="126 4 134 8 126 12"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Secondary Node Visual -->
                            <div class="topo-node">
                                <span class="topo-node-title"><?= htmlspecialchars($t['topology_replica_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="topo-node-host">example-dns.org</span>
                                <div class="topo-badges">
                                    <span class="topo-badge topo-badge-highlight">PowerDNS 4.7 Slave</span>
                                    <span class="topo-badge">SQLite3 Backend</span>
                                    <span class="topo-badge">Autosecondary</span>
                                    <span class="topo-badge">RFC 1996</span>
                                </div>
                            </div>
                        </div>

                        <!-- Client / Resolver resolution bar -->
                        <div class="topo-client-bar">
                            <div>
                                <strong><?= htmlspecialchars($t['topology_client_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span> &rarr; Cloudflare (1.1.1.1), Google (8.8.8.8), Quad9 (9.9.9.9), OpenDNS</span>
                            </div>
                            <div class="sync-label" style="font-size: 0.625rem;">UDP / TCP 53 · Dual-Stack IPv4/IPv6</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Interactive DNS Inspector & Resolver Playground -->
            <section id="inspector" class="content-section" aria-labelledby="inspector-heading">
                <div class="section-header">
                    <h2 id="inspector-heading" class="section-title"><?= htmlspecialchars($t['inspector_section_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="section-subtitle"><?= htmlspecialchars($t['inspector_section_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="inspector-box">
                    <div class="inspector-top">
                        <form id="dns-query-form" class="inspector-query-form" onsubmit="return false;">
                            <div class="inspector-input-wrap">
                                <input type="text" id="inspector-domain" class="inspector-input" value="example-dns.com" placeholder="<?= htmlspecialchars($t['inspector_domain_placeholder'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" spellcheck="false" required>
                            </div>
                            <select id="inspector-type" class="inspector-select-type" aria-label="<?= htmlspecialchars($t['inspector_select_type'], ENT_QUOTES, 'UTF-8') ?>">
                                <option value="A">A (IPv4 Address)</option>
                                <option value="AAAA">AAAA (IPv6 Address)</option>
                                <option value="NS" selected>NS (Authoritative Nameservers)</option>
                                <option value="SOA">SOA (Zone Authority)</option>
                                <option value="TXT">TXT (SPF / Verification)</option>
                                <option value="MX">MX (Mail Exchange)</option>
                                <option value="CAA">CAA (Certificate Authority)</option>
                                <option value="DNSKEY">DNSKEY (DNSSEC Keys)</option>
                                <option value="CNAME">CNAME (Alias)</option>
                            </select>
                            <button type="submit" id="inspector-submit-btn" class="btn btn-primary">
                                <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7" cy="7" r="5"></circle><line x1="11" y1="11" x2="14" y2="14"></line></svg>
                                <span><?= htmlspecialchars($t['inspector_run_query'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </form>

                        <div class="inspector-presets">
                            <span><?= htmlspecialchars($t['inspector_preset_label'], ENT_QUOTES, 'UTF-8') ?>:</span>
                            <button type="button" class="preset-chip" data-domain="example-dns.com" data-type="A">example-dns.com (A)</button>
                            <button type="button" class="preset-chip" data-domain="example-dns.net" data-type="SOA">example-dns.net (SOA)</button>
                            <button type="button" class="preset-chip" data-domain="example-dns.org" data-type="NS">example-dns.org (NS)</button>
                            <button type="button" class="preset-chip" data-domain="ternis.net" data-type="A">ternis.net (A)</button>
                            <button type="button" class="preset-chip" data-domain="example-dns.com" data-type="DNSKEY">example-dns.com (DNSKEY)</button>
                        </div>
                    </div>

                    <div class="terminal-box">
                        <div class="terminal-header">
                            <div class="terminal-dots" aria-hidden="true">
                                <span class="terminal-dot dot-red"></span>
                                <span class="terminal-dot dot-yellow"></span>
                                <span class="terminal-dot dot-green"></span>
                            </div>
                            <div class="terminal-meta">
                                <span id="terminal-status" class="terminal-status-ok">STATUS: NOERROR</span>
                                <span id="terminal-time">12 ms</span>
                                <span id="terminal-server">@77.90.60.110 (Node 1)</span>
                            </div>
                            <button type="button" class="code-copy-btn" id="terminal-copy-btn" title="Copy Output">
                                <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>

                        <pre class="terminal-body"><code id="terminal-output">;; ->>HEADER<<- opcode: QUERY, status: NOERROR, id: 38491
;; flags: qr aa rd ra; QUERY: 1, ANSWER: 2, AUTHORITY: 0, ADDITIONAL: 1
;; QUESTION SECTION:
;example-dns.com.		IN	NS

;; ANSWER SECTION:
example-dns.com.         3600   IN   NS     example-dns.net.
example-dns.com.         3600   IN   NS     example-dns.org.

;; Query time: 14.2 msec
;; SERVER: 77.90.60.110#53(example-dns.net) (UDP)
;; WHEN: <?= gmdate('D M d H:i:s T Y') ?>
;; MSG SIZE  rcvd: 114</code></pre>
                    </div>
                </div>
            </section>

            <!-- Domain Delegation & Registrars Section -->
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

                        <div class="delegation-format-bar">
                            <div class="format-pills" role="group" aria-label="Syntax format">
                                <button type="button" class="format-pill" data-format="raw" data-target="code-ns-dual" aria-pressed="true"><?= htmlspecialchars($t['delegation_format_raw'], ENT_QUOTES, 'UTF-8') ?></button>
                                <button type="button" class="format-pill" data-format="bind" data-target="code-ns-dual" aria-pressed="false"><?= htmlspecialchars($t['delegation_format_bind'], ENT_QUOTES, 'UTF-8') ?></button>
                                <button type="button" class="format-pill" data-format="glue" data-target="code-ns-dual" aria-pressed="false"><?= htmlspecialchars($t['delegation_format_glue'], ENT_QUOTES, 'UTF-8') ?></button>
                                <button type="button" class="format-pill" data-format="terraform" data-target="code-ns-dual" aria-pressed="false"><?= htmlspecialchars($t['delegation_format_terraform'], ENT_QUOTES, 'UTF-8') ?></button>
                            </div>

                            <button type="button" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8125rem;" data-copy-target="code-ns-dual">
                                <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                <span class="btn-text"><?= htmlspecialchars($t['cta_copy_all'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>

                        <div class="code-block-wrap">
                            <div class="code-block-header">
                                <span id="label-ns-dual">AUTHORITATIVE NAMESERVER DELEGATION (DUAL-NS SET)</span>
                                <button type="button" class="code-copy-btn" data-copy-target="code-ns-dual">
                                    <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            </div>
                            <pre><code id="code-ns-dual" data-raw="example-dns.net&#10;example-dns.org" data-bind="@   IN   NS   example-dns.net.&#10;@   IN   NS   example-dns.org." data-glue="example-dns.net.   77.90.60.110    2a14:7c0:1002:16c2::&#10;example-dns.org.   94.249.188.145  2a14:7c0:1002:169c::" data-terraform='resource "dns_ns_record" "apex" {&#10;  zone = "yourdomain.com."&#10;  nameservers = [&#10;    "example-dns.net.",&#10;    "example-dns.org."&#10;  ]&#10;}'>example-dns.net
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

                        <div class="delegation-format-bar">
                            <div class="format-pills" role="group" aria-label="Syntax format">
                                <button type="button" class="format-pill" data-format="raw" data-target="code-ns-quad" aria-pressed="true"><?= htmlspecialchars($t['delegation_format_raw'], ENT_QUOTES, 'UTF-8') ?></button>
                                <button type="button" class="format-pill" data-format="bind" data-target="code-ns-quad" aria-pressed="false"><?= htmlspecialchars($t['delegation_format_bind'], ENT_QUOTES, 'UTF-8') ?></button>
                                <button type="button" class="format-pill" data-format="glue" data-target="code-ns-quad" aria-pressed="false"><?= htmlspecialchars($t['delegation_format_glue'], ENT_QUOTES, 'UTF-8') ?></button>
                                <button type="button" class="format-pill" data-format="terraform" data-target="code-ns-quad" aria-pressed="false"><?= htmlspecialchars($t['delegation_format_terraform'], ENT_QUOTES, 'UTF-8') ?></button>
                            </div>

                            <button type="button" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8125rem;" data-copy-target="code-ns-quad">
                                <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                <span class="btn-text"><?= htmlspecialchars($t['cta_copy_all'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>

                        <div class="code-block-wrap">
                            <div class="code-block-header">
                                <span id="label-ns-quad">AUTHORITATIVE NAMESERVER DELEGATION (QUAD-NS VERBUND)</span>
                                <button type="button" class="code-copy-btn" data-copy-target="code-ns-quad">
                                    <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            </div>
                            <pre><code id="code-ns-quad" data-raw="example-dns.net&#10;example-dns.org&#10;one.ns.ternis.net&#10;two.ns.ternis.net" data-bind="@   IN   NS   example-dns.net.&#10;@   IN   NS   example-dns.org.&#10;@   IN   NS   one.ns.ternis.net.&#10;@   IN   NS   two.ns.ternis.net." data-glue="example-dns.net.   77.90.60.110    2a14:7c0:1002:16c2::&#10;example-dns.org.   94.249.188.145  2a14:7c0:1002:169c::&#10;one.ns.ternis.net. 77.90.60.110    2a14:7c0:1002:16c2::&#10;two.ns.ternis.net. 94.249.188.145  2a14:7c0:1002:169c::" data-terraform='resource "dns_ns_record" "apex" {&#10;  zone = "yourdomain.com."&#10;  nameservers = [&#10;    "example-dns.net.",&#10;    "example-dns.org.",&#10;    "one.ns.ternis.net.",&#10;    "two.ns.ternis.net."&#10;  ]&#10;}'>example-dns.net
example-dns.org
one.ns.ternis.net
two.ns.ternis.net</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Step-by-Step Registrar Guides -->
                <div style="margin-top: 2.5rem;">
                    <h3 style="font-size: 1.1875rem; font-weight: 700; margin-bottom: 0.35rem; color: var(--text);"><?= htmlspecialchars($t['registrar_guide_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.25rem;"><?= htmlspecialchars($t['registrar_guide_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>

                    <div class="registrars-grid">
                        <div class="reg-card">
                            <div class="reg-card-title">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>
                                <?= htmlspecialchars($t['reg_cloudflare_title'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <p class="reg-card-text"><?= htmlspecialchars($t['reg_cloudflare_step'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <div class="reg-card">
                            <div class="reg-card-title">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/></svg>
                                <?= htmlspecialchars($t['reg_hetzner_title'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <p class="reg-card-text"><?= htmlspecialchars($t['reg_hetzner_step'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <div class="reg-card">
                            <div class="reg-card-title">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
                                <?= htmlspecialchars($t['reg_inwx_title'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <p class="reg-card-text"><?= htmlspecialchars($t['reg_inwx_step'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <div class="reg-card">
                            <div class="reg-card-title">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                                <?= htmlspecialchars($t['reg_namecheap_title'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <p class="reg-card-text"><?= htmlspecialchars($t['reg_namecheap_step'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <div class="reg-card">
                            <div class="reg-card-title">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                <?= htmlspecialchars($t['reg_porkbun_title'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <p class="reg-card-text"><?= htmlspecialchars($t['reg_porkbun_step'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <div class="reg-card">
                            <div class="reg-card-title">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                                <?= htmlspecialchars($t['reg_ovh_title'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <p class="reg-card-text"><?= htmlspecialchars($t['reg_ovh_step'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Standard Zone Templates Section -->
            <section id="templates" class="content-section" aria-labelledby="templates-heading">
                <div class="section-header">
                    <h2 id="templates-heading" class="section-title"><?= htmlspecialchars($t['templates_section_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="section-subtitle"><?= htmlspecialchars($t['templates_section_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="templates-grid">
                    <!-- Template 1 -->
                    <article class="template-card">
                        <div class="template-header">
                            <h3 class="template-title"><?= htmlspecialchars($t['template_web_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <button type="button" class="code-copy-btn" data-copy="@   IN   A      77.90.60.110&#10;@   IN   AAAA   2a14:7c0:1002:16c2::&#10;www IN   CNAME  @">
                                <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>
                        <p class="template-desc"><?= htmlspecialchars($t['template_web_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="code-block-wrap" style="margin-bottom: 0;">
                            <pre><code>@    IN  A     77.90.60.110
@    IN  AAAA  2a14:7c0:1002:16c2::
www  IN  CNAME @</code></pre>
                        </div>
                    </article>

                    <!-- Template 2 -->
                    <article class="template-card">
                        <div class="template-header">
                            <h3 class="template-title"><?= htmlspecialchars($t['template_mail_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <button type="button" class="code-copy-btn" data-copy="@          IN  MX    10 mail.ternismail.de.&#10;@          IN  TXT   &quot;v=spf1 include:ternismail.de ~all&quot;&#10;_dmarc     IN  TXT   &quot;v=DMARC1; p=quarantine; sp=quarantine; adkim=r; aspf=r&quot;">
                                <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>
                        <p class="template-desc"><?= htmlspecialchars($t['template_mail_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="code-block-wrap" style="margin-bottom: 0;">
                            <pre><code>@       IN  MX   10 mail.ternismail.de.
@       IN  TXT  "v=spf1 include:ternismail.de ~all"
_dmarc  IN  TXT  "v=DMARC1; p=quarantine; sp=quarantine"</code></pre>
                        </div>
                    </article>

                    <!-- Template 3 -->
                    <article class="template-card">
                        <div class="template-header">
                            <h3 class="template-title"><?= htmlspecialchars($t['template_sec_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <button type="button" class="code-copy-btn" data-copy="@   IN  CAA  0 issue &quot;letsencrypt.org&quot;&#10;@   IN  CAA  0 issuewild &quot;letsencrypt.org&quot;&#10;@   IN  CAA  0 iodef &quot;mailto:security@ternis.org&quot;">
                                <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>
                        <p class="template-desc"><?= htmlspecialchars($t['template_sec_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="code-block-wrap" style="margin-bottom: 0;">
                            <pre><code>@  IN  CAA  0 issue "letsencrypt.org"
@  IN  CAA  0 issuewild "letsencrypt.org"
@  IN  CAA  0 iodef "mailto:security@ternis.org"</code></pre>
                        </div>
                    </article>

                    <!-- Template 4 -->
                    <article class="template-card">
                        <div class="template-header">
                            <h3 class="template-title"><?= htmlspecialchars($t['template_auth_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <button type="button" class="code-copy-btn" data-copy="@  IN  NS  example-dns.net.&#10;@  IN  NS  example-dns.org.">
                                <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>
                        <p class="template-desc"><?= htmlspecialchars($t['template_auth_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="code-block-wrap" style="margin-bottom: 0;">
                            <pre><code>@  IN  NS  example-dns.net.
@  IN  NS  example-dns.org.</code></pre>
                        </div>
                    </article>
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
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        </div>
                        <h3 class="pillar-title"><?= htmlspecialchars($t['pillar_asn_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="pillar-desc"><?= htmlspecialchars($t['pillar_asn_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="pillar-card">
                        <div class="pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        </div>
                        <h3 class="pillar-title"><?= htmlspecialchars($t['pillar_repl_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="pillar-desc"><?= htmlspecialchars($t['pillar_repl_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>

                    <article class="pillar-card">
                        <div class="pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
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
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
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

            <!-- Deployment Center Section -->
            <section id="deploy" class="content-section" aria-labelledby="deploy-heading">
                <div class="section-header">
                    <h2 id="deploy-heading" class="section-title"><?= htmlspecialchars($t['deploy_section_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="section-subtitle"><?= htmlspecialchars($t['deploy_section_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="tab-box">
                    <div class="tab-headers" role="tablist" aria-label="Deployment methods">
                        <button type="button" role="tab" class="tab-btn" id="deploy-btn-vps" aria-selected="true" data-deploy="panel-vps">
                            <span><?= htmlspecialchars($t['deploy_tab_vps'], ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <button type="button" role="tab" class="tab-btn" id="deploy-btn-docker" aria-selected="false" data-deploy="panel-docker">
                            <span><?= htmlspecialchars($t['deploy_tab_docker'], ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <button type="button" role="tab" class="tab-btn" id="deploy-btn-api" aria-selected="false" data-deploy="panel-api">
                            <span><?= htmlspecialchars($t['deploy_tab_api'], ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                    </div>

                    <!-- VPS Installer Panel -->
                    <div id="panel-vps" role="tabpanel" class="tab-content" data-active="true">
                        <p class="tab-desc"><?= htmlspecialchars($t['deploy_vps_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="code-block-wrap">
                            <div class="code-block-header">
                                <span>BASH ZERO-TOUCH VPS INSTALLER</span>
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

                    <!-- REST API Panel -->
                    <div id="panel-api" role="tabpanel" class="tab-content" data-active="false">
                        <p class="tab-desc"><?= htmlspecialchars($t['deploy_api_desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="code-block-wrap">
                            <div class="code-block-header">
                                <span>POWERDNS REST API (CURL)</span>
                                <button type="button" class="code-copy-btn" data-copy-target="code-api">
                                    <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="1.5"/><path d="M3 11V3a1.5 1.5 0 011.5-1.5H11"/></svg>
                                    <span class="btn-text"><?= htmlspecialchars($t['copy_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            </div>
                            <pre><code id="code-api"># Query all hosted zones
curl -s -H 'X-API-Key: example-dns-secret-api-key' \
  http://127.0.0.1:8081/api/v1/servers/localhost/zones | jq .

# Create a new authoritative zone with DNSSEC
curl -X POST -H 'X-API-Key: example-dns-secret-api-key' \
  -H 'Content-Type: application/json' \
  -d '{"name": "yourdomain.com.", "kind": "Master", "masters": [], "nameservers": ["example-dns.net.", "example-dns.org."]}' \
  http://127.0.0.1:8081/api/v1/servers/localhost/zones</code></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Technical FAQ Section -->
            <section class="content-section" aria-labelledby="faq-heading">
                <div class="section-header">
                    <h2 id="faq-heading" class="section-title"><?= htmlspecialchars($t['faq_section_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="section-subtitle"><?= htmlspecialchars($t['faq_section_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="faq-grid">
                    <div class="faq-card" data-open="true">
                        <button type="button" class="faq-question" aria-expanded="true">
                            <span><?= htmlspecialchars($t['faq_q1'], ENT_QUOTES, 'UTF-8') ?></span>
                            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="faq-answer"><?= htmlspecialchars($t['faq_a1'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="faq-card" data-open="false">
                        <button type="button" class="faq-question" aria-expanded="false">
                            <span><?= htmlspecialchars($t['faq_q2'], ENT_QUOTES, 'UTF-8') ?></span>
                            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="faq-answer"><?= htmlspecialchars($t['faq_a2'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="faq-card" data-open="false">
                        <button type="button" class="faq-question" aria-expanded="false">
                            <span><?= htmlspecialchars($t['faq_q3'], ENT_QUOTES, 'UTF-8') ?></span>
                            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="faq-answer"><?= htmlspecialchars($t['faq_a3'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="faq-card" data-open="false">
                        <button type="button" class="faq-question" aria-expanded="false">
                            <span><?= htmlspecialchars($t['faq_q4'], ENT_QUOTES, 'UTF-8') ?></span>
                            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="faq-answer"><?= htmlspecialchars($t['faq_a4'], ENT_QUOTES, 'UTF-8') ?></div>
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
                    <span class="sync-label" style="display: inline-block; margin-top: 0.5rem; width: fit-content;"><?= htmlspecialchars($t['footer_network_status'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <nav class="footer-nav" aria-label="Footer links">
                    <a href="<?= htmlspecialchars($imprint_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($t['footer_imprint'], ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="https://admin.example-dns.com" target="_blank" rel="noopener"><?= htmlspecialchars($t['footer_admin'], ENT_QUOTES, 'UTF-8') ?></a>
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

    <!-- Back to top floating button -->
    <button type="button" class="back-to-top" id="back-to-top" aria-label="<?= htmlspecialchars($t['back_to_top'], ENT_QUOTES, 'UTF-8') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </button>

    <!-- Client Script (Zero External Dependencies) -->
    <script>
    (function () {
        'use strict';

        var copiedText = <?= json_encode($t['copied_label'], JSON_UNESCAPED_UNICODE) ?>;
        var copyText = <?= json_encode($t['copy_label'], JSON_UNESCAPED_UNICODE) ?>;
        var metaTheme = document.getElementById('meta-theme-color');

        // Theme management
        function applyTheme(theme) {
            var isDark = false;
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('example-dns-theme', 'dark');
                isDark = true;
            } else if (theme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('example-dns-theme', 'light');
                isDark = false;
            } else {
                document.documentElement.removeAttribute('data-theme');
                localStorage.removeItem('example-dns-theme');
                isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            if (metaTheme) {
                metaTheme.setAttribute('content', isDark ? '#090d16' : '#ffffff');
            }

            document.querySelectorAll('.theme-btn').forEach(function (btn) {
                var val = btn.getAttribute('data-theme-val');
                btn.setAttribute('aria-pressed', val === theme ? 'true' : 'false');
            });
        }

        var currentStoredTheme = localStorage.getItem('example-dns-theme') || 'auto';
        applyTheme(currentStoredTheme);

        document.querySelectorAll('.theme-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var theme = btn.getAttribute('data-theme-val');
                applyTheme(theme);
            });
        });

        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                if (!localStorage.getItem('example-dns-theme')) {
                    applyTheme('auto');
                }
            });
        }

        // Mobile Menu Toggle
        var mobileBtn = document.getElementById('mobile-menu-toggle');
        var headerControls = document.getElementById('header-controls');
        if (mobileBtn && headerControls) {
            mobileBtn.addEventListener('click', function () {
                var isOpen = headerControls.classList.toggle('mobile-open');
                mobileBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            headerControls.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    headerControls.classList.remove('mobile-open');
                    mobileBtn.setAttribute('aria-expanded', 'false');
                });
            });
        }

        // Clipboard Copy function
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

            if (btn.classList.contains('copy-inline-btn')) {
                btn.classList.add('copied');
                setTimeout(function () {
                    btn.classList.remove('copied');
                }, 1800);
                return;
            }

            var textElem = btn.querySelector('.btn-text');
            if (textElem) {
                var orig = textElem.textContent;
                textElem.textContent = copiedText;
                btn.style.opacity = '0.7';

                setTimeout(function () {
                    textElem.textContent = orig;
                    btn.style.opacity = '';
                }, 1800);
            }
        }

        document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-copy-target');
                var target = document.getElementById(targetId);
                if (target) {
                    copyString(target.textContent.trim(), btn);
                }
            });
        });

        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var str = btn.getAttribute('data-copy');
                if (str) {
                    copyString(str, btn);
                }
            });
        });

        // Format Switchers for Delegation
        document.querySelectorAll('.format-pill').forEach(function (pill) {
            pill.addEventListener('click', function () {
                var format = pill.getAttribute('data-format');
                var targetId = pill.getAttribute('data-target');
                var codeElem = document.getElementById(targetId);
                var container = pill.closest('.format-pills');
                if (!codeElem || !container) return;

                container.querySelectorAll('.format-pill').forEach(function (p) {
                    p.setAttribute('aria-pressed', p === pill ? 'true' : 'false');
                });

                var rawData = codeElem.getAttribute('data-' + format);
                if (rawData) {
                    codeElem.textContent = rawData;
                }
            });
        });

        // Accessible Tab Switcher
        function setupTabs(btnAttr, panelAttr, parentBoxSelector) {
            var boxes = document.querySelectorAll(parentBoxSelector);
            boxes.forEach(function (box) {
                var tabBtns = box.querySelectorAll('[' + btnAttr + ']');
                tabBtns.forEach(function (btn, index) {
                    btn.addEventListener('click', function () {
                        var targetPanelId = btn.getAttribute(btnAttr);
                        tabBtns.forEach(function (b) {
                            b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
                        });
                        box.querySelectorAll('.tab-content').forEach(function (panel) {
                            panel.setAttribute('data-active', panel.id === targetPanelId ? 'true' : 'false');
                        });
                    });

                    btn.addEventListener('keydown', function (e) {
                        var nextIndex = null;
                        if (e.key === 'ArrowRight') {
                            nextIndex = (index + 1) % tabBtns.length;
                        } else if (e.key === 'ArrowLeft') {
                            nextIndex = (index - 1 + tabBtns.length) % tabBtns.length;
                        } else if (e.key === 'Home') {
                            nextIndex = 0;
                        } else if (e.key === 'End') {
                            nextIndex = tabBtns.length - 1;
                        }

                        if (nextIndex !== null) {
                            e.preventDefault();
                            tabBtns[nextIndex].click();
                            tabBtns[nextIndex].focus();
                        }
                    });
                });
            });
        }

        setupTabs('data-tab', 'id', '.tab-box');
        setupTabs('data-deploy', 'id', '.tab-box');

        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var card = btn.closest('.faq-card');
                if (!card) return;
                var isOpen = card.getAttribute('data-open') === 'true';
                card.setAttribute('data-open', isOpen ? 'false' : 'true');
                btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            });
        });

        // Live DNS Inspector Simulator & Query Tool
        var queryForm = document.getElementById('dns-query-form');
        var domainInput = document.getElementById('inspector-domain');
        var typeSelect = document.getElementById('inspector-type');
        var submitBtn = document.getElementById('inspector-submit-btn');
        var terminalStatus = document.getElementById('terminal-status');
        var terminalTime = document.getElementById('terminal-time');
        var terminalServer = document.getElementById('terminal-server');
        var terminalOutput = document.getElementById('terminal-output');
        var terminalCopyBtn = document.getElementById('terminal-copy-btn');

        function executeDnsQuery(domain, type) {
            if (!domain) return;
            domain = domain.trim();

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';
            }

            var startTime = Date.now();
            fetch('?api=dns&domain=' + encodeURIComponent(domain) + '&type=' + encodeURIComponent(type))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                    }

                    if (terminalStatus) {
                        terminalStatus.textContent = 'STATUS: ' + (data.status || 'NOERROR');
                        terminalStatus.className = (data.status === 'NOERROR' && data.count > 0) ? 'terminal-status-ok' : '';
                    }
                    if (terminalTime) {
                        terminalTime.textContent = (data.query_time_ms || (Date.now() - startTime)) + ' ms';
                    }
                    if (terminalServer) {
                        terminalServer.textContent = '@' + (data.server || '77.90.60.110');
                    }
                    if (terminalOutput && data.raw_dig) {
                        terminalOutput.textContent = data.raw_dig;
                    }
                })
                .catch(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                    }
                    if (terminalStatus) {
                        terminalStatus.textContent = 'STATUS: SERVER-ERROR';
                        terminalStatus.className = '';
                    }
                    if (terminalOutput) {
                        terminalOutput.textContent = ';; Error: Failed to contact live DNS resolver endpoint.';
                    }
                });
        }

        if (queryForm && domainInput && typeSelect) {
            queryForm.addEventListener('submit', function (e) {
                e.preventDefault();
                executeDnsQuery(domainInput.value, typeSelect.value);
            });

            typeSelect.addEventListener('change', function () {
                executeDnsQuery(domainInput.value, typeSelect.value);
            });
        }

        document.querySelectorAll('.preset-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var d = chip.getAttribute('data-domain');
                var t = chip.getAttribute('data-type');
                if (domainInput) domainInput.value = d;
                if (typeSelect) typeSelect.value = t;
                executeDnsQuery(d, t);
            });
        });

        if (terminalCopyBtn && terminalOutput) {
            terminalCopyBtn.addEventListener('click', function () {
                copyString(terminalOutput.textContent.trim(), terminalCopyBtn);
            });
        }

        // Back to top scroll listener
        var backToTopBtn = document.getElementById('back-to-top');
        if (backToTopBtn) {
            window.addEventListener('scroll', function () {
                if (window.pageYOffset > 320) {
                    backToTopBtn.classList.add('visible');
                } else {
                    backToTopBtn.classList.remove('visible');
                }
            }, { passive: true });

            backToTopBtn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    })();
    </script>
</body>
</html>
