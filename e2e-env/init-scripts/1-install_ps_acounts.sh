#!/bin/sh
set -eu
cd "$(dirname $0)" || exit 1

# Download and install the module's zip
GITHUB_REPOSITORY="PrestaShopCorp/ps_accounts"
TARGET_ASSET="ps_accounts_preprod-${PS_ACCOUNTS_VERSION#v}.zip"
TARGET_VERSION=${PS_ACCOUNTS_VERSION}
if [[ "$TARGET_VERSION" == *"beta"* ]]; then
    CLEANED_VERSION="${PS_ACCOUNTS_VERSION%-beta.*}" 
   TARGET_ASSET="ps_accounts_preprod-${CLEANED_VERSION#v}.zip"
fi
echo "* [ps_accounts] downloading..."
wget -q -O /tmp/ps_accounts.zip "https://github.com/${GITHUB_REPOSITORY}/releases/download/${TARGET_VERSION}/${TARGET_ASSET}"
echo "* [ps_accounts] unziping..."
unzip -qq /tmp/ps_accounts.zip -d /var/www/html/modules
echo "* [ps_accounts] installing the module..."
cd "$PS_FOLDER"
php -d memory_limit=-1 bin/console prestashop:module --no-interaction install "ps_accounts"