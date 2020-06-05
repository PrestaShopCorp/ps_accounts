#!/bin/bash -e

generateMainDirectory(){
    PATH_TO_INSTALL=$1
    echo $PATH_TO_INSTALL
    # TODO remove this line
    sudo rm -rf $PATH_TO_INSTALL || true
    mkdir -p $PATH_TO_INSTALL
    cd $PATH_TO_INSTALL
}

installServices() {
    echo 'install services'
    git clone  --single-branch --branch feature/docker-compose git@github.com:PrestaShopCorp/services.git
    cd services
    bash ./install.sh $1/services
	# cp -n apps/accounts/api/.env.example apps/accounts/api/.env || true
	cp -n /home/david/Workspace/PrestaShop/services/apps/accounts/api/.env apps/accounts/api/.env || true
	# cp -n apps/accounts/ui/.env.example apps/accounts/ui/.env || true
    cp -n /home/david/Workspace/PrestaShop/services/apps/accounts/ui/.env apps/accounts/ui/.env || true
    docker-compose up -d
    cd ..
}

installPrestashopAccountsAuth() {
    echo 'install prestashop_accounts_auth'
    git clone  --single-branch --branch feature/170-adaptor-to-lib-npm git@github.com:PrestaShopCorp/prestashop_accounts_auth.git
    cd prestashop_accounts_auth
    composer install
    cd ..
}

installPrestashopAccountsVueComponents() {
    echo 'install prestashop_accounts_vue_components'
    git clone  --single-branch --branch feature/build-lib git@github.com:PrestaShopCorp/prestashop_accounts_vue_components.git
    cd prestashop_accounts_vue_components
    yarn
    yarn build-lib
    cd ..
}

installPsCheckout() {
    echo 'install ps_checkout'
    git clone  --single-branch --branch test/account-integration git@github.com:v4lux/ps_checkout.git
    cd ps_checkout/_dev
    # TODO remove this lines
    rm -rf ./package.json
    cp  /home/david/Workspace/PrestaShop/ps_checkout/_dev/package.json ./package.json

    yarn

    # TODO remove this lines
    yarn add $1/prestashop_accounts_vue_components
    yarn --cwd _dev/ build
}

installPsAccounts() {
    echo 'install ps_accounts'
    git clone  --single-branch --branch feature/170-adaptor-to-lib-npm git@github.com:PrestaShopCorp/ps_accounts.git
    cd ps_accounts
    cp -n /home/david/Workspace/PrestaShop/ps_accounts/.env .env || true

    make start
}

main(){
    generateMainDirectory $1
    installServices $1
    installPrestashopAccountsAuth
    installPrestashopAccountsVueComponents
    installPsCheckout $1
    installPsAccounts
}

main $*
echo 'FINISH'
