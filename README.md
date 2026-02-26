# Web3 WP Plugins

This repo hosts WordPress plugins for the Origin/DDNS ecosystem.

## Structure
- `wp-content/plugins/` all WordPress plugins
- `shared/` shared PHP SDKs and UI helpers

## Plugins (purpose + status)
- `wp-content/plugins/ddns-optin` — Opt-in form + shortcode + health check. **Status: MVP**
- `wp-content/plugins/toll-comments` — Refundable toll for comments. **Status: MVP**
- `wp-content/plugins/tolldns` — TollDNS activation/status plugin used by AI WebAdmin guardrails. **Status: MVP**
- `wp-content/plugins/ai-webadmin` — AI WebAdmin WordPress plugin (worker moderation, hardening, backup/sandbox controls). **Status: Active Split**
- `wp-content/plugins/ddns-node` — Cache helper node. **Status: Next**
- `wp-content/plugins/ddns-accelerator` — Admin wizard for caching worker + asset export. **Status: Next**
- `wp-content/plugins/ddns-compat-orchestrator` — Compatibility checks. **Status: Later**
- `wp-content/plugins/ddns-ai-admin` — Stub. **Status: Later**
- `wp-content/plugins/contributor-bounties` — Content bounties. **Status: Later**

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
