# DDNS Node

## Purpose
WordPress cache/witness node for DECENTRALIZED-DNS resolver results.

This plugin exposes a small REST API that proxies resolver lookups to the upstream worker/coordinator and caches responses locally. It can optionally emit best-effort telemetry.

## Endpoints
- `GET /wp-json/ddns/v1/health`
  - Returns `{ ok: true, upstream_ok: boolean, cache_entries: number }`

- `GET /wp-json/ddns/v1/resolve?name=example.com[&proof=0|1]`
  - Proxies to upstream: `${ddns_node_worker_url}/resolve?name=...`
  - Caches successful JSON responses
  - Returns JSON with cache metadata

## Response contract (MVP)
Returns the upstream resolver response, plus a `metadata` object:
- `metadata.source`: `cache` or `worker`
- `metadata.cache`: `hit` | `miss` | `bypass`
- `metadata.cached_at`: unix ms (when cached; only on cache hit)
- `metadata.ttl`: seconds used for caching

Do not cache 5xx. Cache 4xx only if explicitly enabled (default: no).

## Config (WP Admin settings)
- `ddns_node_worker_url` (required) Upstream base URL (used for `/resolve` + `/healthz`).
- `ddns_node_site_id` Defaulted from site host; included in telemetry payloads.
- `ddns_node_site_token` Optional auth token sent as `x-ddns-site-token`.
- `ddns_node_enable_telemetry` Enables 3-minute cron reporting (best-effort).
- `ddns_node_otlp_endpoint` Optional OTLP traces endpoint (Grafana).
- `ddns_node_otlp_auth` Optional Basic auth value (without `Authorization:` prefix).
- `ddns_node_cache_ttl_seconds` Default TTL (fallback when upstream TTL missing). Default: 60.
- `ddns_node_cache_max_entries` Soft limit for cached entries. Default: 500.

## Caching rules (MVP)
- Cache key: normalized `name` (lowercase, trim trailing dot). Includes `proof` flag.
- Store the full JSON response and a stored timestamp.
- TTL: `min(upstream_ttl, ddns_node_cache_ttl_seconds)` if upstream has TTL, else `ddns_node_cache_ttl_seconds`.
- Eviction: when over `ddns_node_cache_max_entries`, delete oldest entries (best-effort).

## Notes
- Secrets should be configured in the control-plane or environment, not stored in WordPress.
- No private keys are stored.
- Proof checks are structural only in MVP (cryptographic verification can be added later).
- "Witness" means the node stores upstream responses + timestamps to compare divergence later (no crypto receipts yet).
- Telemetry never blocks request handling.
