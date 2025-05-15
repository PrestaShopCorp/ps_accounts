#!/bin/bash

# remove install folder
cd /var/www/html && rm -rf ./install

if [ -f .env ]; then
  if [ "$PS_TRUSTED_PROXIES" ]; then
    # starting with prestashop v9
    export TRUSTED_PROXIES=`echo $PS_TRUSTED_PROXIES | sed 's@/@\\\/@g'`
    sed -i -e "s/\(PS_TRUSTED_PROXIES=\).*/\1${TRUSTED_PROXIES}/" .env
  fi
fi

if [ "$PHYSICAL_URI" ]; then
  cd /var/www
  # fix htaccess
  sed -i -e "s/\[E=REWRITEBASE:[^\]]*\]/[E=REWRITEBASE:\/${PHYSICAL_URI}\/]/" ./html/.htaccess
  # move into subdirectory
  mv html $PHYSICAL_URI && mkdir html && mv $PHYSICAL_URI html/
  # update physical uri
  mysql -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD $DB_NAME \
    -e "UPDATE ps_shop_url SET physical_uri='/$PHYSICAL_URI/'"
fi

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
# Install ps_accounts module
cd "$PS_ROOT"
php -d memory_limit=-1 bin/console prestashop:module --no-interaction install "ps_accounts"
# Change logs file persmission
chown -R $CHOWN_USER "$PS_ROOT/var/logs"
chmod g+r -R "$PS_ROOT/var/logs"
# Change cache file persmission
chown -R $CHOWN_USER "$PS_ROOT/var/cache"
chmod g+r -R "$PS_ROOT/var/cache"