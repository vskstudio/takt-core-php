#!/usr/bin/env bash
set -euo pipefail
# Vendored bundle is the auto snippet (takt.auto.js): base tracker + opt-in
# autocapture (outbound / downloads / tagged / 404) driven by data-* attributes.
# Pinned so the committed bundle is reproducible; bump deliberately on upgrade.
VERSION="${1:-0.5.1}"
DEST="$(dirname "$0")/../resources/takt.auto.js"
PKG="@vskstudio/takt-core@${VERSION}/dist/takt.auto.js"
# Deux miroirs CDN : jsdelivr peut renvoyer un 502 transitoire sur un cache froid
# (premier hit d'une version fraîchement publiée). On réessaie puis on bascule sur
# unpkg pour ne pas rendre la CI flaky sur un aléa CDN.
MIRRORS=("https://cdn.jsdelivr.net/npm/${PKG}" "https://unpkg.com/${PKG}")
for url in "${MIRRORS[@]}"; do
  echo "fetching $url"
  if curl -fsSL --retry 3 --retry-all-errors "$url" -o "$DEST"; then
    echo "wrote $DEST ($(wc -c < "$DEST") bytes)"
    exit 0
  fi
  echo "miroir indisponible, bascule suivante…" >&2
done
echo "échec de récupération du bundle takt-core@${VERSION} sur tous les miroirs" >&2
exit 1
