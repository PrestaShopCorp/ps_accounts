#!/bin/sh
#
# Fails when the module version has no upgrade script registering it.
#
# PS_ACCOUNTS_LAST_UPGRADE is only written by migrate_or_create_identities_v8(),
# called from the upgrade script of the version being installed. Without
# upgrade/upgrade-<version>.php, the flag stays on the previous version and
# every upgraded shop shows the "reset your PS Account module" banner (ACC-3701).
#
# Usage: check-upgrade-script.sh [version]
#   version defaults to the one in config.xml; when given (release tag), the
#   module files must carry that version too.

rootDir="."

configVersion=$(grep '<version>' "${rootDir}/config.xml" | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/')
constVersion=$(grep 'const VERSION = ' "${rootDir}/ps_accounts.php" | sed "s/^.*'\(.*\)'.*$/\1/")
propertyVersion=$(grep '\$this->version = ' "${rootDir}/ps_accounts.php" | sed "s/^.*'\(.*\)'.*$/\1/")

version=${1:-$configVersion}
errors=0

fail() {
  echo "::error::$1"
  errors=$((errors + 1))
}

for found in "$configVersion" "$constVersion" "$propertyVersion"; do
  if [ "$found" != "$version" ]; then
    fail "Module version mismatch: expected ${version}, got config.xml=${configVersion}, VERSION=${constVersion}, \$this->version=${propertyVersion}. Merge the version bump PR before releasing."
    break
  fi
done

script="${rootDir}/upgrade/upgrade-${version}.php"
upgradeFunction=$(echo "upgrade_module_${version}" | tr '.' '_')

if [ ! -f "$script" ]; then
  fail "Missing ${script}: shops upgrading to ${version} would keep a stale PS_ACCOUNTS_LAST_UPGRADE."
else
  if ! grep -q "function ${upgradeFunction}(" "$script"; then
    fail "${script} must define ${upgradeFunction}()."
  fi
  if ! grep -q "migrate_or_create_identities_v8(\$module, '${version}')" "$script"; then
    fail "${script} must call migrate_or_create_identities_v8(\$module, '${version}')."
  fi
fi

if [ "$errors" -gt 0 ]; then
  exit 1
fi

echo "upgrade/upgrade-${version}.php registers version ${version}"
