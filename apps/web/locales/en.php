<?php
declare(strict_types=1);

return [
    'lang_code' => 'en',
    'lang_name' => 'English',
    'title' => 'example-dns — Open-Source Authoritative DNS Infrastructure',
    'tagline' => 'High-availability authoritative nameservers and resilient DNS infrastructure.',
    'seo_keywords' => 'DNS, PowerDNS, authoritative nameserver, open source, AXFR, DNSSEC, SQLite, MariaDB',
    'project_by' => 'An open-source infrastructure initiative by <a href="https://ternis.org" target="_blank" rel="noopener">ternis.org</a> (powered by <a href="https://ternis.dev" target="_blank" rel="noopener">ternis.dev</a> &amp; <a href="https://ternis.net" target="_blank" rel="noopener">ternis.net</a>).',


    // Header & Navigation
    'skip_to_content' => 'Skip to main content',
    'nav_admin' => 'Admin Portal',

    // Hero
    'hero_title' => 'Autonomous Authoritative DNS Infrastructure',
    'hero_description' => 'example-dns provides high-availability authoritative nameservers powered by PowerDNS with dual-node IP diversity across independent servers, real-time AXFR replication, and native DNSSEC.',
    'cta_admin' => 'Admin Portal',
    'cta_github' => 'GitHub',
    'cta_codeberg' => 'Codeberg',

    // Delegation
    'delegation_title' => 'Authoritative Nameservers',
    'delegation_desc' => 'Delegate your domain to our nameserver cluster using either standard FQDNs or glue records:',
    'copy_label' => 'Copy',
    'copied_label' => 'Copied!',

    // Nodes
    'nodes_title' => 'Cluster Nodes',
    'node_primary_name' => 'Primary Master',
    'node_primary_host' => 'example-dns.net',
    'node_primary_desc' => 'PowerDNS 4.9 · MariaDB 11.8 · REST API · 77.90.60.110 · 2a14:7c0:1002:16c2::',
    'node_secondary_name' => 'Secondary Replica',
    'node_secondary_host' => 'example-dns.org',
    'node_secondary_desc' => 'PowerDNS 4.7 · SQLite3 · Autosecondary · 94.249.188.145 · 2a14:7c0:1002:169c::',
    'node_web_name' => 'Web Gateway',
    'node_web_host' => 'example-dns.com',
    'node_web_desc' => 'Caddy v2.10 · PHP 8.5 · On-Demand TLS · 77.90.60.110',

    // Footer
    'footer_text' => 'Released under the <a href="https://opensource.org/licenses/MIT" target="_blank" rel="noopener">MIT License</a>. Maintained by <a href="mailto:f.ternis@xpsystems.eu">Fabian Ternis</a>.',
    'imprint' => 'Legal &amp; Imprint',
];
