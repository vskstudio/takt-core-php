#!/usr/bin/env bash
set -euo pipefail
# Vendored bundle is the auto snippet (takt.auto.js): base tracker + opt-in
# autocapture (outbound / downloads / tagged / 404) driven by data-* attributes.
# Pinned so the committed bundle is reproducible; bump deliberately on upgrade.
VERSION="${1:-0.4.2}"
URL="https://cdn.jsdelivr.net/npm/@vskstudio/takt-core@${VERSION}/dist/takt.auto.js"
DEST="$(dirname "$0")/../resources/takt.auto.js"
echo "fetching $URL"
curl -fsSL "$URL" -o "$DEST"
echo "wrote $DEST ($(wc -c < "$DEST") bytes)"
