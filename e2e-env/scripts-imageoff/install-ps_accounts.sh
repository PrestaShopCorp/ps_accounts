set -eu
cd "$(dirname $0)" || exit 1

# Le zip du module : celui construit localement s'il est monté (flux PR,
# on teste le code de la branche), sinon la release publiée (flux TNR).
LOCAL_ZIP="/local-module/ps_accounts.zip"
GITHUB_REPOSITORY="PrestaShopCorp/ps_accounts"
TARGET_VERSION=${PS_ACCOUNTS_VERSION:-}

if echo "$TARGET_VERSION" | grep -q "beta"; then
    CLEANED_VERSION="${TARGET_VERSION%-beta*}"
else
    CLEANED_VERSION="${TARGET_VERSION}"
fi

TARGET_ASSET="ps_accounts_preprod-${CLEANED_VERSION#v}.zip"

# Définition des variables
PS_ROOT="/var/www/html/${PHYSICAL_URI:-}"
CHOWN_USER="www-data:www-data"

# Fetch ps_accounts module
if [ -f "$LOCAL_ZIP" ]; then
  echo "* [ps_accounts] using locally built zip [${LOCAL_ZIP}]"
  cp "$LOCAL_ZIP" /tmp/ps_accounts.zip
else
  echo "* [ps_accounts] downloading..."
  echo "https://github.com/${GITHUB_REPOSITORY}/releases/download/${TARGET_VERSION}/${TARGET_ASSET}"
  wget -q -O /tmp/ps_accounts.zip "https://github.com/${GITHUB_REPOSITORY}/releases/download/${TARGET_VERSION}/${TARGET_ASSET}"
fi

# Unzip ps_accounts module
echo "* [ps_accounts] unzipping..."
rm -rf "$PS_ROOT/modules/ps_accounts"
unzip -o -qq /tmp/ps_accounts.zip -d "$PS_ROOT/modules"

# Change permission
chown -R $CHOWN_USER "$PS_ROOT/modules/ps_accounts"
chmod -R 775 "$PS_ROOT/modules/ps_accounts"

# Créer les répertoires de cache
echo "* [ps_accounts] preparing cache directories..."
mkdir -p "$PS_ROOT/var/cache/prod/ps_accounts"
mkdir -p "$PS_ROOT/var/cache/dev/ps_accounts"
mkdir -p "$PS_ROOT/var/logs"

# Donner les permissions appropriées
chown -R $CHOWN_USER "$PS_ROOT/var/cache"
chown -R $CHOWN_USER "$PS_ROOT/var/logs"
chmod -R 775 "$PS_ROOT/var/cache"
chmod -R 775 "$PS_ROOT/var/logs"

# Pré-créer le fichier de log du jour avec les bonnes permissions
LOG_FILE="$PS_ROOT/var/logs/ps_accounts-$(date +%Y-%m-%d)"
touch "$LOG_FILE"
chown $CHOWN_USER "$LOG_FILE"
chmod 666 "$LOG_FILE"

# Nettoyer UNIQUEMENT le cache du module (pas tout!)
rm -f "$PS_ROOT/var/cache/prod/ps_accounts/"* 2>/dev/null || true
rm -f "$PS_ROOT/var/cache/dev/ps_accounts/"* 2>/dev/null || true

# Install ps_accounts module
echo "* [ps_accounts] installing module..."
if command -v runuser >/dev/null 2>&1; then
  runuser -u www-data -- php -d memory_limit=-1 "$PS_ROOT/bin/console" prestashop:module --no-interaction install "ps_accounts"
else
  su -s /bin/sh www-data -c "php -d memory_limit=-1 \"$PS_ROOT/bin/console\" prestashop:module --no-interaction install ps_accounts"
fi

# Vérifier et corriger les permissions après installation
echo "* [ps_accounts] fixing permissions after installation..."
chown -R $CHOWN_USER "$PS_ROOT"/{var,modules/ps_accounts}
chmod -R 775 "$PS_ROOT"/{var,modules/ps_accounts}

echo "* [ps_accounts] installation completed!"
