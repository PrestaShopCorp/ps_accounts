#Install PS_ACCOUNTS
set -eu
cd "$(dirname $0)" || exit 1

# Download and install the module's zip
GITHUB_REPOSITORY="PrestaShopCorp/ps_accounts"
TARGET_VERSION=${PS_ACCOUNTS_VERSION}

if echo "$PS_ACCOUNTS_VERSION" | grep -q "beta"; then
    CLEANED_VERSION="${PS_ACCOUNTS_VERSION%-beta*}" 
else
    CLEANED_VERSION="${PS_ACCOUNTS_VERSION}" 
fi

TARGET_ASSET="ps_accounts_preprod-${CLEANED_VERSION#v}.zip"

# Définition des variables
PS_ROOT="/var/www/html/${PHYSICAL_URI:-}"
CHOWN_USER="www-data:www-data"

# Download ps_accounts module
echo "* [ps_accounts] downloading..."
echo "https://github.com/${GITHUB_REPOSITORY}/releases/download/${TARGET_VERSION}/${TARGET_ASSET}"
wget -q -O /tmp/ps_accounts.zip "https://github.com/${GITHUB_REPOSITORY}/releases/download/${TARGET_VERSION}/${TARGET_ASSET}"

# Unzip ps_accounts module
echo "* [ps_accounts] unzipping..."
unzip -qq /tmp/ps_accounts.zip -d "$PS_ROOT/modules"

# Change permission
chown -R $CHOWN_USER "$PS_ROOT/modules/ps_accounts"
chmod g+r -R "$PS_ROOT/modules/ps_accounts"

# Créer les répertoires de cache
echo "* [ps_accounts] preparing cache directories..."
mkdir -p "$PS_ROOT/var/cache/prod/ps_accounts"
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

# Nettoyer le fichier de cache s'il existe
rm -f "$PS_ROOT/var/cache/prod/ps_accounts/openid-configuration.json" || true

# Install ps_accounts module
echo "* [ps_accounts] installing module..."
cd "$PS_ROOT"
php -d memory_limit=-1 bin/console prestashop:module --no-interaction install "ps_accounts"

# Vérifier et corriger les permissions après installation
echo "* [ps_accounts] fixing permissions after installation..."
chown -R $CHOWN_USER "$PS_ROOT/var/cache"
chmod -R 775 "$PS_ROOT/var/cache"
chown -R $CHOWN_USER "$PS_ROOT/var/logs"
chmod -R 775 "$PS_ROOT/var/logs"
echo "* [ps_accounts] installation completed!"
