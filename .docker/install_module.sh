#!/bin/sh

wget -qO- https://deb.nodesource.com/setup_12.x | bash -
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list

apt update \
    && apt install -y vim nano wget nodejs yarn \
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

rm -rf /var/www/html/modules/ps_checkout/_dev/node_modules/prestashop_accounts_vue_components
ln -s /tmp/libs/js/prestashop_accounts_vue_components /var/www/html/modules/ps_checkout/_dev/node_modules/prestashop_accounts_vue_components

rm -rf /var/www/html/var/cache/*;
