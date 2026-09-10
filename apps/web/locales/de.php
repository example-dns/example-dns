<?php
declare(strict_types=1);

return [
    'lang_code' => 'de',
    'lang_name' => 'Deutsch',
    'title' => 'example-dns — Open-Source autoritative DNS-Infrastruktur',
    'tagline' => 'Hochverfügbare autoritative Nameserver und ausfallsichere DNS-Infrastruktur.',
    'seo_keywords' => 'DNS, PowerDNS, autoritativer Nameserver, Open Source, AXFR, DNSSEC, SQLite, MariaDB',
    'project_by' => 'Eine Open-Source-Infrastrukturinitiative von <a href="https://ternis.org" target="_blank" rel="noopener">ternis.org</a> (bereitgestellt durch <a href="https://ternis.dev" target="_blank" rel="noopener">ternis.dev</a> &amp; <a href="https://ternis.net" target="_blank" rel="noopener">ternis.net</a>).',
    'badge_status' => 'Autoritativer Cluster aktiv',
    'badge_open_source' => '100% Open Source · MIT',

    // Header & Navigation
    'skip_to_content' => 'Zum Hauptinhalt springen',
    'nav_admin' => 'Admin-Portal',

    // Hero
    'hero_title' => 'Autonome autoritative DNS-Infrastruktur',
    'hero_description' => 'example-dns bietet hochverfügbare autoritative Nameserver auf Basis von PowerDNS mit IP-Diversität über getrennte Server-Nodes hinweg, Echtzeit-AXFR-Replikation und nativem DNSSEC.',
    'cta_admin' => 'Admin-Portal',
    'cta_github' => 'GitHub',
    'cta_codeberg' => 'Codeberg',

    // Delegation
    'delegation_title' => 'Autoritative Nameserver',
    'delegation_desc' => 'Delegieren Sie Ihre Domain an unseren Nameserver-Cluster über FQDNs oder Glue-Records:',
    'copy_label' => 'Kopieren',
    'copied_label' => 'Kopiert!',

    // Nodes
    'nodes_title' => 'Cluster-Nodes',
    'node_primary_name' => 'Primärer Master',
    'node_primary_host' => 'example-dns.net',
    'node_primary_desc' => 'PowerDNS 4.9 · MariaDB 11.8 · REST-API · 77.90.60.110 · 2a14:7c0:1002:16c2::',
    'node_secondary_name' => 'Sekundäres Replikat',
    'node_secondary_host' => 'example-dns.org',
    'node_secondary_desc' => 'PowerDNS 4.7 · SQLite3 · Autosecondary · 94.249.188.145 · 2a14:7c0:1002:169c::',
    'node_web_name' => 'Web-Gateway',
    'node_web_host' => 'example-dns.com',
    'node_web_desc' => 'Caddy v2.10 · PHP 8.5 · On-Demand TLS · 77.90.60.110',

    // Footer
    'footer_text' => 'Veröffentlicht unter der <a href="https://opensource.org/licenses/MIT" target="_blank" rel="noopener">MIT-Lizenz</a>. Entwickelt &amp; gepflegt von <a href="mailto:f.ternis@xpsystems.eu">Fabian Ternis</a>.',
    'imprint' => 'Rechtliches &amp; Impressum',
];
