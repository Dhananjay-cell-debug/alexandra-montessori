#!/usr/bin/env bash
set -euo pipefail
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
if command -v python3 >/dev/null 2>&1; then
  exec python3 "$script_dir/deploy-ldrn.py" "$@"
else
  exec python "$script_dir/deploy-ldrn.py" "$@"
fi
