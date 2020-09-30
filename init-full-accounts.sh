#!/bin/bash -e

generateMainDirectory(){
    rm -rf $1 || true
    mkdir -p $1
    cd $1
}

gitClone(){
    echo 'git clone'
    cd $1
    git clone git@github.com:PrestaShopCorp/services.git
    git clone --single-branch --branch feature/account-integration git@github.com:PrestaShopCorp/ps_checkout.git
    git clone git@github.com:PrestaShopCorp/prestashop_accounts_auth.git
    git clone git@github.com:PrestaShopCorp/prestashop_accounts_vue_components.git
    git clone git@github.com:PrestaShopCorp/ps_accounts.git
    echo 'git clone finished'
}

installServices() {
    echo 'install services'
    cd $1/services
    docker-compose up -d
    echo 'install services finished'
}

installPrestashopAccountsAuth() {
    echo 'install prestashop_accounts_auth'
    cd $1/prestashop_accounts_auth
    composer install
    echo 'install prestashop_accounts_auth finished'
}

installPrestashopAccountsVueComponents() {
    echo 'install prestashop_accounts_vue_components'
    cd $1/prestashop_accounts_vue_components
    yarn install
    yarn build-lib
    echo 'install prestashop_accounts_vue_components finished'
}

installPsCheckout() {
    echo 'install ps_checkout'
    cd $1/ps_checkout/_dev
    yarn install
    yarn build
    echo 'install ps_checkout finished'
}

installPsMetrics() {
    echo 'install ps_metrics'
    cd $1/ps_metrics/_dev
    yarn install
    yarn build
    echo 'install ps_metrics finished'
}

installPsAccounts() {
    echo 'install ps_accounts'
    cd $1/ps_accounts
    make start
    echo 'install ps_accounts finished'
}

prepare(){
    echo 'prepare'
    generateMainDirectory $1
    gitClone $1
    cd $1/ps_accounts
    make init
    echo 'prepare finished'
}

install(){
    echo 'install'
    installServices $1
    installPrestashopAccountsAuth $1
    installPrestashopAccountsVueComponents $1
    installPsAccounts $1
    installPsCheckout $1
    installPsMetrics $1
    echo 'install finished'
}

"$@"
