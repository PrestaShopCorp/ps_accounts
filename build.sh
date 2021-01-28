#!/bin/bash
set -e
error () { echo -e "\033[1;31m$1\033[0m"; }
cd "$(dirname "$0")"

# Get the current version from git and perform checks
VERSION=$(git describe --tags)
[[ -z "$VERSION" ]] && error "no version: are you running this from a git repository?" && exit 1
[[ -n "$(git status -s)" ]] && error "cannot proceed: you have unstaged or uncommitted changes." && exit 2
[[ -z "$(which php)" ]] && error "cannot proceed: you need a php environment to build this package." && exit 3

MODULE="ps_accounts"
PACKAGE="${MODULE}-${VERSION}"
ZIP="${PACKAGE}_raw.zip"
ZIP_INTE="${PACKAGE}_inte.zip"
ZIP_PROD="${PACKAGE}_prod.zip"
ORIGIN=$(pwd)
TMP_DIR="$(mktemp -d)"
DIST="${ORIGIN}/dist"

# Copy the sources and prepare dist
cp -r "$(pwd)" "${TMP_DIR}/${MODULE}"
cd "${TMP_DIR}/${MODULE}"
mkdir -p "$DIST"

# Build vendors
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
./composer.phar install --no-dev
rm ./composer.phar;

# Pack a zip for integration
cd "${TMP_DIR}/${MODULE}"
mv ".env.inte" ".env"
cd ..
zip -r ${ZIP_INTE} ${MODULE} -x '*.git*'
mv ${ZIP_INTE} ${DIST}

# Pack a zip for production
cd "${TMP_DIR}/${MODULE}"
mv ".env.prod" ".env"
cd ..
zip -r ${ZIP_PROD} ${MODULE} -x '*.git*'
mv ${ZIP_PROD} ${DIST}

# Cleanup
rm -rf "$TMP_DIR"
