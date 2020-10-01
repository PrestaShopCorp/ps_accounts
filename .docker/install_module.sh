#!/bin/sh

LIB_PATH=/tmp/libs/php/prestashop_accounts_auth/

# git branch to be referenced into composer-dev.json
parse_git_branch() {
  cd $LIB_PATH;
  if [ `which git` ]; then
    git branch 2> /dev/null | sed -e '/^[^*]/d' -e 's/* \(.*\)/\1/' -e 's/\//\\\//';
  else
    echo 'master'
  fi
}

general_install(){
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
}

module_installer(){
  build_vue_components
  for module in ps_accounts ps_checkout ps_metrics
  do
      cd /var/www/html/modules/$module;
      composer_install
      build_module_front $module
      /var/www/html/bin/console --env=prod prestashop:module install $module
  done
}

composer_install(){
  rm -f composer-dev.json composer-dev.lock;
  cp composer.json composer-dev.json;
  sed -i "/^}/i \\
    ,\"repositories\": [\\
      {\\
        \"type\": \"path\",\\
        \"url\": \"`echo $LIB_PATH | sed -e 's/\//\\//'`\"\\
      }\\
    ]" composer-dev.json;
  sed -i "s/\(\"prestashop\/prestashop-accounts-auth\"\): \"[^\"]*\"/\1: \"dev-$(parse_git_branch)\"/g" composer-dev.json;
  composer install;
}

build_vue_components() {
  yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/
  yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/ build-lib --fix
  yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/ build-lib
}

build_module_front() {
  yarn --cwd /var/www/html/modules/$1/_dev/
  yarn --cwd /var/www/html/modules/$1/_dev/ build --fix
  yarn --cwd /var/www/html/modules/$1/_dev/ build

  rm -rf /var/www/html/modules/$1/_dev/node_modules/prestashop_accounts_vue_components
  ln -s /tmp/libs/js/prestashop_accounts_vue_components /var/www/html/modules/$1/_dev/node_modules/prestashop_accounts_vue_components
}

main(){
  general_install
  build_vue_components
  module_installer
  rm -rf /var/www/html/var/cache/*;
}

main $*
