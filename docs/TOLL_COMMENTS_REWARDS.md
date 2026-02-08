# Toll Comments Rewards

## Site Reward Pool
Each site has a **Site Reward Pool** tracked in the credits coordinator.

Sources of funds:
- Verified node receipts (credits to pool)
- Forfeited comment tolls (split)

Defaults:
- Forfeit split: 50% pool / 50% treasury
- Pool daily cap (configurable in coordinator)

## Bonus payouts
When a comment is **approved**, the toll is refunded. If bonus is enabled, the bonus is paid from the Site Reward Pool.

Anti-abuse rules:
- Bonus requires an approved comment.
- Per-wallet bonus daily caps enforced in the coordinator.
- Pool must have sufficient balance to pay bonus.

## Coordinator endpoints
- `POST /comments/hold`
- `POST /comments/submit`
- `POST /comments/finalize`
- `GET /site-pool?site_id=<id>`
- `GET /public/ledger`
