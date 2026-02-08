# Stake Verification (Placeholder)

This repo expects stake verification to gate plugin features (including CDN/S3 enablement).
The verification mechanism is not wired yet. Use this document to fill in the final details.

## RPC (Solana)
Public endpoints (rate-limited):
- Devnet: https://api.devnet.solana.com
- Testnet: https://api.testnet.solana.com
- Mainnet-beta: https://api.mainnet-beta.solana.com

## Required inputs (to be filled)
- Stake program ID:
- Which account proves stake (token account, PDA, escrow vault):
- Minimum stake amount:
- Mapping rule from wallet -> stake account (seeds, derivation):

## Recommended flow
1) Coordinator verifies stake on-chain and caches results.
2) WordPress plugins call coordinator for `stake: true/false`.

Direct on-chain verification from PHP is possible but slower and more fragile due to RPC rate limits.
