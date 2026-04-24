#!/bin/sh
set -eu

PS_ROOT="${PS_FOLDER:-/var/www/html}"
TARGET_FILE="${PS_ROOT}/admin-dev/themes/default/template/controllers/modules/js.tpl"
PS_VERSION_VALUE="$(php -r "require '${PS_ROOT}/config/config.inc.php'; echo _PS_VERSION_;" 2>/dev/null || true)"

case "$PS_VERSION_VALUE" in
  1.6.*)
    ;;
  *)
    echo "* [ps_accounts] skipping AdminModules refresh patch for ${PS_VERSION_VALUE:-unknown}"
    exit 0
    ;;
esac

if [ ! -f "$TARGET_FILE" ]; then
  echo "* [ps_accounts] AdminModules js.tpl not found, skipping patch"
  exit 0
fi

if grep -q 'action : "refreshModuleList"' "$TARGET_FILE"; then
  sed -i 's/action : "refreshModuleList"/action : "noopRefreshModuleList"/' "$TARGET_FILE"
  echo "* [ps_accounts] disabled AdminModules refreshModuleList ajax call"
else
  echo "* [ps_accounts] AdminModules refreshModuleList ajax call already disabled"
fi
