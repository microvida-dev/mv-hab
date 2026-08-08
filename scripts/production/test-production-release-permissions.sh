#!/usr/bin/env bash
set -Eeuo pipefail

if [[ "$(uname -s)" != "Linux" ]]; then
    echo "PRODUCTION_RELEASE_PERMISSION_TEST=NOT_APPLICABLE_NON_LINUX"
    exit 0
fi

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
LIB="$ROOT/scripts/production/lib/mvhab-production-runtime.sh"

# shellcheck disable=SC1090
source "$LIB"

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

mkdir -p "$TMP/public/assets" "$TMP/bin"
printf '%s\n' '<?php echo "ok";' > "$TMP/public/index.php"
printf '%s\n' 'asset' > "$TMP/public/assets/app.css"
printf '%s\n' '#!/usr/bin/env bash' 'exit 0' > "$TMP/bin/tool.sh"

chmod 0750 "$TMP"
chmod 0775 "$TMP/public" "$TMP/public/assets" "$TMP/bin"
chmod 0664 "$TMP/public/index.php" "$TMP/public/assets/app.css"
chmod 0775 "$TMP/bin/tool.sh"

APP_USER="$(id -un)"
APP_GROUP="$(id -gn)"

mvhab_normalize_release_permissions \
    "$TMP" \
    "$APP_USER" \
    "$APP_GROUP"

mvhab_assert_release_permissions \
    "$TMP" \
    "$APP_USER" \
    "$APP_GROUP"

mvhab_assert_web_traversal \
    "$TMP" \
    "$APP_USER"

[[ "$(stat -c '%a' "$TMP")" == "755" ]]
[[ "$(stat -c '%a' "$TMP/public")" == "755" ]]
[[ "$(stat -c '%a' "$TMP/public/index.php")" == "644" ]]
[[ "$(stat -c '%a' "$TMP/public/assets/app.css")" == "644" ]]
[[ "$(stat -c '%a' "$TMP/bin/tool.sh")" == "755" ]]

echo "PRODUCTION_RELEASE_PERMISSION_TEST=PASS"
