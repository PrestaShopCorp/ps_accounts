#!/bin/bash
# >Note: add your "GITHUB_API_TOKEN" in your .bashrc or .zshrc or whatever env file you use
set -e
error () { echo -e "\033[1;31m$1\033[0m"; }
MODULE="ps_accounts"
REPOSITORY="PrestaShopCorp"
PACKAGE="${MODULE}-${VERSION}"
ZIP="${PACKAGE}_raw.zip"
ZIP_INTE="${PACKAGE}_inte.zip"
ZIP_PROD="${PACKAGE}_prod.zip"
ORIGIN=$(pwd)
TMP_DIR="$(mktemp -d)"

# Get the last version released
VERSION=$(git tag | tail -1 | sed 's/v//') # ex. "3.0.1"
[ -z "$VERSION" ] && error "no version: are you running this from a git repository?" && exit 1

# Download the last version sourcew from the releases page: https://github.com/PrestaShopCorp/ps_accounts/releases
curl \
  -L -H "Authorization: token ${GITHUB_API_TOKEN}" \
  "https://api.github.com/repos/${REPOSITORY}/${MODULE}/zipball/v${VERSION}" \
  --output "${TMP_DIR}/${ZIP}"

# Unpack the zip
unzip -o "${TMP_DIR}/${ZIP}" -d ${TMP_DIR}
ZIP_FOLDER=$(find ${TMP_DIR} -type d -name "${REPOSITORY}-${MODULE}-*";)
mv "${ZIP_FOLDER}" "${TMP_DIR}/${MODULE}"

# Build vendors
cd "${TMP_DIR}/${MODULE}"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
./composer.phar install --no-dev
rm ./composer.phar;

# Pack a zip for integration
cd "${TMP_DIR}/${MODULE}"
mv .env.inte > .env
cd ..
zip -r ${ZIP_INTE} ${MODULE}
mv ${ZIP_INTE} ${ORIGIN}

# Pack a zip for production
cd "${TMP_DIR}/${MODULE}"
mv .env.prod > .env
cd ..
zip -r ${ZIP_PROD} ${MODULE}
mv ${ZIP_PROD} ${ORIGIN}

# Cleanup
rm -rf "${PACKAGE}" "$ZIP" "$TMP_DIR"