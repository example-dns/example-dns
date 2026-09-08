# System Architecture

`example-dns` is organized as a monorepo containing the applications, services, and infrastructure required to run the `example-dns` open-source DNS network.

## Domain Roles & Infrastructure

The ecosystem spans three designated domains, each serving a distinct architectural role:

| Domain | Role | Description | Monorepo Path |
| --- | --- | --- | --- |
| **`example-dns.com`** | Web Portal & API | The administrative web dashboard, user account management, and REST/gRPC API. | [`apps/web`](../apps/web) |
| **`example-dns.net`** | Primary Nameserver | The primary authoritative DNS nameserver responsible for accepting record updates and zone mastering. | [`apps/nameserver-primary`](../apps/nameserver-primary) |
| **`example-dns.org`** | Secondary Nameserver | The secondary authoritative DNS nameserver providing redundancy, geographic distribution, and zone synchronization. | [`apps/nameserver-secondary`](../apps/nameserver-secondary) |

## High-Level Architecture

```mermaid
flowchart TD
    User([End User / Admin]) -->|HTTPS| WebApp[example-dns.com<br/>Web Interface & API]
    WebApp -->|Zone Updates / API| PrimaryNS[example-dns.net<br/>Primary Nameserver]
    PrimaryNS -->|Zone Transfer AXFR/IXFR / Sync| SecondaryNS[example-dns.org<br/>Secondary Nameserver]
    
    DNSClient([Public DNS Resolvers]) -->|DNS Query UDP/TCP 53| PrimaryNS
    DNSClient -->|DNS Query UDP/TCP 53| SecondaryNS
```

## Monorepo Layout

```
.
├── apps/
│   ├── web/                     # example-dns.com web application & API
│   ├── nameserver-primary/      # example-dns.net authoritative primary nameserver
│   └── nameserver-secondary/    # example-dns.org authoritative secondary nameserver
├── infra/                       # Infrastructure-as-code, Docker, and deployment manifests
├── docs/                        # Architectural specifications and project documentation
├── LICENSE                      # MIT License
└── README.md                    # Project landing page and overview
```
