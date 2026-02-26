#!/usr/bin/env bash
set -euo pipefail
cd /Users/root1/dev/web3-repos/web3-wp-plugins

out_dir="dist"
rm -rf "$out_dir"
mkdir -p "$out_dir"

python3 - <<'PY'
import re
import zipfile
from pathlib import Path

root = Path('/Users/root1/dev/web3-repos/web3-wp-plugins')
exclude = {'.git', 'node_modules', 'dist', '__pycache__'}

plugin_dirs = []
plugins_root = root / 'wp-content' / 'plugins'
if plugins_root.exists():
    for plugin in plugins_root.iterdir():
        if plugin.is_dir():
            plugin_dirs.append(plugin)

for plugin in plugin_dirs:
    main = None
    for php in plugin.glob('*.php'):
        txt = php.read_text(errors='ignore')
        if 'Plugin Name:' in txt:
            main = php
            break
    if not main:
        continue
    txt = main.read_text(errors='ignore')
    m = re.search(r'\n\s*\*\s*Version:\s*([^\n]+)', txt)
    version = m.group(1).strip() if m else '0.1.0'
    zip_path = root / 'dist' / f"{plugin.name}-{version}.zip"
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
        for file in plugin.rglob('*'):
            if file.is_dir():
                continue
            if any(part in exclude for part in file.parts):
                continue
            arcname = f"{plugin.name}/" + str(file.relative_to(plugin))
            zf.write(file, arcname)
    print(f"packaged {zip_path}")
PY
