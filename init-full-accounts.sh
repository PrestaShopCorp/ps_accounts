#!/bin/bash

set -e

export CURRENT_UID=`id -u`
export CURRENT_GID=`id -g`
export CURRENT_USER="${CURRENT_UID}:${CURRENT_GID}"

gitClone(){
    echo 'git clone'
    cd $1
    git clone git@github.com:PrestaShopCorp/services.git
    git clone --single-branch --branch feature/account-integration git@github.com:PrestaShopCorp/ps_checkout.git
    git clone git@github.com:PrestaShopCorp/prestashop_accounts_auth.git
    git clone git@github.com:PrestaShopCorp/prestashop_accounts_vue_components.git
    git clone git@github.com:PrestaShopCorp/ps_accounts.git
    echo 'git clone finished'
    popd
}

installServices() {
    echo 'install services'
    pushd $1/services
    docker-compose up -d
    echo 'install services finished'
    popd
}

installPrestashopAccountsAuth() {
    echo 'install prestashop_accounts_auth'
    pushd $1/prestashop_accounts_auth
    composer install
    echo 'install prestashop_accounts_auth finished'
    popd
}

installPrestashopAccountsVueComponents() {
    echo 'install prestashop_accounts_vue_components'
    pushd $1/prestashop_accounts_vue_components
    yarn install
    yarn build-lib
    echo 'install prestashop_accounts_vue_components finished'
    popd
}

installPsCheckout() {
    echo 'install ps_checkout'
    pushd $1/ps_checkout/_dev
    yarn install
    yarn build
    echo 'install ps_checkout finished'
    popd
}

installPsAccounts() {
    echo 'install ps_accounts'
    pushd $1/ps_accounts
    make start
    echo 'install ps_accounts finished'
    popd
}

prepare(){
    echo 'prepare'
    gitClone $1
    pushd $1/ps_accounts
    make init
    echo 'prepare finished'
    popd
}

install(){
    echo 'install'
    installServices $1
    installPrestashopAccountsAuth $1
    installPrestashopAccountsVueComponents $1
    installPsCheckout $1
    installPsAccounts $1
    echo 'install finished'
}

"$@"
