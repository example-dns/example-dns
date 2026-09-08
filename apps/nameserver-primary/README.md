# example-dns Primary Nameserver (`example-dns.net`)

This directory houses the authoritative primary nameserver software and service configuration for `example-dns.net`.

## Responsibilities

- Authoritative DNS resolution on port 53 (UDP/TCP)
- Zone file mastering and database-backed dynamic record serving
- Zone transfer coordination (AXFR/IXFR and NOTIFY) to secondary nameservers
