# Web3 WP Plugins

This repo hosts WordPress plugins for the Origin/DDNS ecosystem.

## Structure
- `plugins/` all plugins (WordPress-ready)
- `shared/` shared PHP SDKs and UI helpers

## Plugins (purpose + status)
- `plugins/ddns-optin` — Opt-in form + shortcode + health check. **Status: MVP**
- `plugins/toll-comments` — Refundable toll for comments. **Status: MVP**
- `plugins/ddns-node` — Cache helper node. **Status: Next**
- `plugins/ddns-accelerator` — Admin wizard for caching worker + asset export. **Status: Next**
- `plugins/ddns-compat-orchestrator` — Compatibility checks. **Status: Later**
- `plugins/ddns-ai-admin` — Stub. **Status: Later**
- `plugins/contributor-bounties` — Content bounties. **Status: Later**

## Shared
- `shared/ddns-sdk-php` — coordinator/client HTTP helper for MVP plugins.

## Local WordPress
```bash
/Users/root1/dev/web3-repos/web3-wp-plugins/scripts/wp-up.sh
```

Stop:
```bash
/Users/root1/dev/web3-repos/web3-wp-plugins/scripts/wp-down.sh
```

## Node Mode Harness
```bash
docker compose -f /Users/root1/dev/web3-repos/web3-wp-plugins/docker-compose.node-mode.yml up --build
```

## Package Plugins
```bash
/Users/root1/dev/web3-repos/web3-wp-plugins/scripts/package.sh
```

## Docs
- docs/WP_NODE_MODE.md
- docs/TOLL_COMMENTS_REWARDS.md
