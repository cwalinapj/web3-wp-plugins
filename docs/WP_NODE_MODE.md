# WP Node Mode (Toll Comments)

## Overview
Node Mode lets a WordPress site contribute spare capacity by caching and serving verified `/resolve` responses. It is **opt-in** and **off by default**.

## What it does
- Prefetches and caches verified `/resolve` responses.
- Serves verified responses at:
  - `/wp-json/ddns/v1/resolve?name=<domain>`
- Health check:
  - `/wp-json/ddns/v1/health`
- Submits Ed25519-signed receipts to the coordinator to credit the Site Reward Pool.

## Settings
Settings → **DDNS Toll Comments** → **Node mode**:
- Enable Node Mode
- Resolver URL (source of truth)
- Hot names (comma-separated)
- Node name (should exist in `.dns` registry)
- Max disk (MB)
- Max bandwidth (MB/day)
- Max CPU percent (soft)
- Active hours (UTC, e.g., `00:00-23:59`)

## Coordinator requirements
- `COMMENTS_SITE_TOKEN` configured on the coordinator and in WP settings.
- Node receipts are accepted only with valid `verification_id` from `/node/verify`.
- Node pubkey must be registered in `.dns` as a `NODE_PUBKEY` record.

## Local test harness (Docker)
```bash
docker compose -f docker-compose.node-mode.yml up --build
```

Then in WP settings:
- Coordinator URL: `http://mock-coordinator:8054`
- Site token: `dev-token`

## Safety
- Only verified responses are cached and served.
- No open proxy behavior.
- Resource caps are respected.
