#!/usr/bin/env bash
set -euo pipefail
VERSION="${1:-latest}"
URL="https://cdn.jsdelivr.net/npm/@vskstudio/takt-core@${VERSION}/dist/takt.js"
DEST="$(dirname "$0")/../resources/takt.js"
echo "fetching $URL"
curl -fsSL "$URL" -o "$DEST"
echo "wrote $DEST ($(wc -c < "$DEST") bytes)"
