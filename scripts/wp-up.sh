#!/usr/bin/env bash
set -euo pipefail
cd /Users/root1/dev/web3-repos/web3-wp-plugins

docker compose up -d

echo "WordPress running at http://localhost:8085"
