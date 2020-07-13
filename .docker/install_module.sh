#!/bin/sh

groupmod -g $UID www-data
usermod -u $UID -g $GID www-data

wget -qO- https://deb.nodesource.com/setup_12.x | bash -
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list

apt update \
    && apt install -y vim nano wget nodejs yarn \
    && php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer \
    && rm -rf /var/lib/apt/lists/*

version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/amd64/$version \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get ('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8707\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
    && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz

for module in ps_accounts ps_checkout
do
    cd /var/www/html/modules/$module;
    composer install;
    /var/www/html/bin/console --env=prod prestashop:module install $module
    rm -rf /var/www/html/modules/$module/vendor/prestashop/prestashop-accounts-auth
    ln -s /tmp/libs/php/prestashop_accounts_auth /var/www/html/modules/$module/vendor/prestashop/prestashop-accounts-auth
done

yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/
yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/ build-lib
yarn --cwd /var/www/html/modules/ps_checkout/_dev/
yarn --cwd /var/www/html/modules/ps_checkout/_dev/ build

rm -rf /var/www/html/modules/ps_checkout/_dev/node_modules/prestashop_accounts_vue_components
ln -s /tmp/libs/js/prestashop_accounts_vue_components /var/www/html/modules/ps_checkout/_dev/node_modules/prestashop_accounts_vue_components

rm -rf /var/www/html/var/cache/*;
