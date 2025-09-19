#!/usr/bin/env bash
set -euo pipefail

# Simple helper to fetch a badge locally and verify logo embedding.
# Usage:
#   scripts/fetch-badge.sh -u <username> [-r <repository>] [--label "Label Text"] [--style flat] [--color blue] [--logo-file path.png|path.svg] [--logo-slug github] [--logo-size 32] [--abbreviated]
#   scripts/fetch-badge.sh -u me --logo-file assets/icon.png --label "Profile Views"
#   scripts/fetch-badge.sh -u me --logo-slug laravel --style for-the-badge
#
# Output:
#   Saves SVG to badge.svg (or path specified by --out) and prints a short status line
#   indicating whether an <image> element is present.

BASE_URL="${BADGE_BASE_URL:-https://ghvc.kabelkultur.se}" # allow override for local instance
OUT_FILE="badge.svg"
USERNAME=""
REPO=""
LABEL=""
STYLE=""
COLOR=""
LOGO_FILE=""
LOGO_SLUG=""
LOGO_SIZE=""
ABBREV=false

urlencode() {
  # percent-encode via Python for reliability
  python - <<'PY'
import sys, urllib.parse
print(urllib.parse.quote(sys.stdin.read().strip(), safe=''))
PY
}

die() { echo "Error: $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    -u|--username) USERNAME="$2"; shift 2;;
    -r|--repository) REPO="$2"; shift 2;;
    --label) LABEL="$2"; shift 2;;
    --style) STYLE="$2"; shift 2;;
    --color) COLOR="$2"; shift 2;;
    --logo-file) LOGO_FILE="$2"; shift 2;;
    --logo-slug) LOGO_SLUG="$2"; shift 2;;
    --logo-size) LOGO_SIZE="$2"; shift 2;;
    --out) OUT_FILE="$2"; shift 2;;
    --abbreviated) ABBREV=true; shift 1;;
    -h|--help)
      grep '^#' "$0" | sed 's/^# \{0,1\}//'; exit 0;;
    *) die "Unknown argument: $1";;
  esac
done

[[ -n "$USERNAME" ]] || die "--username required"

QUERY="username=$(printf '%s' "$USERNAME" | urlencode)"
[[ -n "$REPO" ]] && QUERY+="&repository=$(printf '%s' "$REPO" | urlencode)"
[[ -n "$LABEL" ]] && QUERY+="&label=$(printf '%s' "$LABEL" | urlencode)"
[[ -n "$STYLE" ]] && QUERY+="&style=$(printf '%s' "$STYLE" | urlencode)"
[[ -n "$COLOR" ]] && QUERY+="&color=$(printf '%s' "$COLOR" | urlencode)"
[[ -n "$LOGO_SIZE" ]] && QUERY+="&logoSize=$(printf '%s' "$LOGO_SIZE" | urlencode)"
$ABBREV && QUERY+="&abbreviated=true"

if [[ -n "$LOGO_SLUG" && -n "$LOGO_FILE" ]]; then
  die "Use either --logo-file or --logo-slug, not both"
fi

if [[ -n "$LOGO_SLUG" ]]; then
  QUERY+="&logo=$(printf '%s' "$LOGO_SLUG" | urlencode)"
fi

if [[ -n "$LOGO_FILE" ]]; then
  [[ -f "$LOGO_FILE" ]] || die "Logo file not found: $LOGO_FILE"
  # Determine mime
  EXT="${LOGO_FILE##*.}"; EXT_LOWER="${EXT,,}"
  case "$EXT_LOWER" in
    png) MIME="image/png";;
    jpg|jpeg) MIME="image/jpeg";;
    gif) MIME="image/gif";;
    svg) MIME="image/svg+xml";;
    *) die "Unsupported extension: $EXT_LOWER";;
  esac
  if [[ "$MIME" == "image/svg+xml" ]]; then
    DATA_URI="data:image/svg+xml;base64,$(base64 -w0 < "$LOGO_FILE")"
  else
    DATA_URI="data:$MIME;base64,$(base64 -w0 < "$LOGO_FILE")"
  fi
  ENCODED=$(printf '%s' "$DATA_URI" | urlencode)
  QUERY+="&logo=$ENCODED"
fi

URL="$BASE_URL/?$QUERY"

curl -sS "$URL" -o "$OUT_FILE" || die "curl failed"
if grep -q '<image' "$OUT_FILE"; then
  echo "Saved $OUT_FILE (logo embedded) -> $URL"
else
  echo "Saved $OUT_FILE (no <image> element) -> $URL" >&2
fi
