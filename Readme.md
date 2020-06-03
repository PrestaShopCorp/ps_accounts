# PrestaShop Account


## AOS

An AOS module is in three parts:

### [module ps_accounts](http://github.com/PrestaShopCorp/ps_accounts)

* [Autoinstall](http://github.com/PrestaShopCorp/ps_accounts)
 (lien doc)
* Contains all the controllers

### [librairie npm](http://github.com/PrestaShopCorp/prestashop_accounts_vue_components)

* Should be installed in the AOS module `npm install prestashop_accounts_vue_components` or `yarn add prestashop_accounts_vue_components`
* Contains all the vuejs components to manage onboarding

### [librairie composer](http://github.com/PrestaShopCorp/prestashop_accounts_auth)

* Should be installed in the AOS module `composer require prestashop/prestashop-accounts-auth`
* contient tout la lib composer:
    * wrappe tout les appel au module ps_accounts
    * contient tout la logique de firebase




## Installation

Clone the repo if not already done

```bash
git clone git@github.com:PrestaShopCorp/prestashop_accounts_auth.git
git clone git@github.com:PrestaShopCorp/ps_checkout.git
make init
```
/!\ Customize .env with path of dependencies

Customize docker-compose.override.yml for choice port

## Usage

List all Makefile rules
```bash
make
```

Start project :
```bash
make start
```

Wait container be to state `healthy`

For watch docker-compose status run `watch docker-compose ps`

![](doc/healthy.png)

### How to connect ?

#### FRONT

```
FRONT URL   : http://localhost:<port>
User        : pub@prestashop.com
Pwd         : 123456789
```

#### BO



authentication
```
URL   : http://localhost:<port>/admin-dev
User        : demo@prestashop.com
Pwd         : prestashop_demo
```

or

```
http://127.0.0.1:<port>/admin-dev/index.php/module/manage?email=demo@prestashop.com password=prestashop_demo
```

#### DB

```
host        : localhost
port        : <port>
name        : prestashop
user        : macfly
password    : admin
prefix      : ps_
```

## Testing

Run php-cs-fixer
```bash
php vendor/bin/php-cs-fixer fix
```

Run phpstan for prestashop 1.6.1.0

```bash
docker run -tid --rm -v ps-volume:/var/www/html --name temp-ps prestashop/prestashop:1.6.1.0;

docker run --rm --volumes-from temp-ps -v $PWD:/web/module -e _PS_ROOT_DIR_=/var/www/html --workdir=/web/module phpstan/phpstan:0.12 analyse --configuration=/web/module/tests/phpstan/phpstan-PS-1.6.neon
```

Run phpstan for prestashop 1.7.0.3

```bash
docker run -tid --rm -v ps-volume:/var/www/html --name temp-ps prestashop/prestashop:1.7.0.3;

docker run --rm --volumes-from temp-ps -v $PWD:/web/module -e _PS_ROOT_DIR_=/var/www/html --workdir=/web/module phpstan/phpstan:0.12 analyse --configuration=/web/module/tests/phpstan/phpstan-PS-1.7.neon
```
