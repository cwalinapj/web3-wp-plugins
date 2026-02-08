# DDNS Node

## Purpose
WP cache/witness node for DECENTRALIZED-DNS resolver results.

## Endpoints
- `/wp-json/ddns/v1/health`
- `/wp-json/ddns/v1/resolve?name=example.com`

## Config
- `DDNS_UPSTREAM_URL` (env) or `ddns_node_upstream_url` option.

## Notes
No private keys are stored. Proof checks are structural only (cryptographic verification can be added later).
