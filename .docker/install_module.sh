#!/bin/sh

apt update \
    && apt install -y vim nano wget \
    && php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer \
    && rm -rf /var/lib/apt/lists/*

for module in ps_accounts ps_checkout
do
    cd /var/www/html/modules/$module;
    composer install;
    /var/www/html/bin/console --env=prod prestashop:module install $module
    rm -rf /var/www/html/modules/$module/vendor/prestashop/prestashop-accounts-auth
    ln -s /tmp/libs/php/prestashop_accounts_auth /var/www/html/modules/$module/vendor/prestashop/prestashop-accounts-auth
done

rm -rf /var/www/html/var/cache/*;
