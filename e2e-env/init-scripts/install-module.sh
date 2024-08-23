#!/bin/sh
#
# This is an init-script for prestashop-flashlight.
#
# Storing a folder in /var/www/html/modules is not enough to register the module
# into PrestaShop, hence why we have to call the console install CLI.
#
set -eu

error() {
  printf "\e[1;31m%s\e[0m\n" "${1:-Unknown error}"
  exit "${2:-1}"
}

ps_accounts_install() {
  # Notice: you might enable this if your uid is not 1000, or encounter permission issues
  # composer install -n -d ./modules/ps_accounts
  echo "* [ps_accounts] installing the module..."
  cd "$PS_FOLDER"
  php -d memory_limit=-1 bin/console prestashop:module --no-interaction install "ps_accounts"
}

ps_accounts_install

