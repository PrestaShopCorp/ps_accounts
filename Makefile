SHELL = /bin/bash -o pipefail
MODULE_NAME = ps_accounts
VERSION ?= $(shell git describe --tags 2> /dev/null || echo "v0.0.0")
SEM_VERSION ?= $(shell echo ${VERSION} | sed 's/^v//')
BRANCH_NAME ?= $(shell git rev-parse --abbrev-ref HEAD | sed -e 's/\//_/g')
PACKAGE ?= ${MODULE_NAME}-${VERSION}
PS_VERSION ?= 8.1.7
TESTING_IMAGE ?= prestashop/prestashop-flashlight:${PS_VERSION}
PS_ROOT_DIR ?= $(shell pwd)/prestashop/prestashop-${PS_VERSION}
WORKDIR ?= ./

PLATFORM_REPO ?= prestashop/prestashop-flashlight
PLATFORM_REPO_TAG ?= 8.1.5-7.4
PLATFORM_IMAGE ?= ${PLATFORM_REPO}:${PHPUNIT_TAG}
PLATFORM_COMPOSE_FILE ?= docker-compose.flashlight.yml
COMPOSER_FILE ?= composer.json
BUNDLE_JS ?= views/js/app.${SEM_VERSION}.js
COMPOSER_OPTIONS ?= --prefer-dist -o --no-dev --quiet
CONTAINER_INSTALL_DIR="/var/www/html/modules/ps_accounts"

export PHP_CS_FIXER_IGNORE_ENV = 1
export _PS_ROOT_DIR_ ?= ${PS_ROOT_DIR}
export PATH := ./vendor/bin:./tests/vendor/bin:$(PATH)

# target: (default)                                            - Build the module
default: build

# target: build                                                - Install dependencies and build assets
.PHONY: build
build: dist php-scoper vendor tests/vendor build-front

# target: help                                                 - Get help on this file
.PHONY: help
help:
	@echo -e "##\n# ${MODULE_NAME}:\n#  version: ${VERSION}\n#  branch: ${BRANCH_NAME}\n##"
	@egrep "^# target" Makefile

# target: clean                                                - Clean up the repository (but keep you .npmrc)
.PHONY: clean
clean:
	git clean -X -f --exclude="!.npmrc" --exclude="!.env*"

# target: vendor-clean                                         - Remove composer dependencies
.PHONY: vendor-clean
vendor-clean:
	rm -rf ./vendor tests/vendor

# target: clean-deps                                           - Remove composer and npm dependencies
.PHONY: clean-deps
clean-deps: vendor-clean
	rm -rf ./_dev/node_modules

# target: zip                                                  - Make all zip bundles
.PHONY: zip
zip: zip-local zip-preprod zip-prod

# target: zip-local                                            - Bundle a local E2E compatible zip
.PHONY: zip-local
zip-local: dist php-scoper build-front
	$(eval PKG_LOCAL := $(if $(filter main,$(BRANCH_NAME)),${PACKAGE},${PACKAGE}-${BRANCH_NAME}))
	$(call zip_it,.config.local.yml,${PKG_LOCAL}-local.zip)

# target: zip-preprod                                          - Bundle a pre-production zip
.PHONY: zip-preprod
zip-preprod: dist php-scoper build-front
	$(eval PKG_PREPROD := $(if $(filter main,$(BRANCH_NAME)),${PACKAGE},${PACKAGE}-${BRANCH_NAME}))
	$(call zip_it,.config.preprod.yml,${PKG_PREPROD}_preprod.zip)

# target: zip-prod                                             - Bundle a production zip
.PHONY: zip-prod
zip-prod: dist php-scoper build-front
	$(eval PKG_PROD := $(if $(filter main,$(BRANCH_NAME)),${PACKAGE},${PACKAGE}-${BRANCH_NAME}))
	$(call zip_it,.config.prod.yml,${PKG_PROD}.zip)

dist:
	@mkdir -p ./dist 

${BUNDLE_JS}: _dev/node_modules
	pnpm --filter ./_dev build

.PHONY: build-front
build-front: _dev/node_modules ${BUNDLE_JS}

_dev/node_modules:
	pnpm --filter ./_dev install

composer.phar:
	@php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');";
	@php composer-setup.php;
	@php -r "unlink('composer-setup.php');";

vendor: composer.phar
	./composer.phar install ${COMPOSER_OPTIONS}

tests/vendor: composer.phar
	./composer.phar install --working-dir tests -o

prestashop:
	@mkdir -p ./prestashop

prestashop/prestashop-${PS_VERSION}: prestashop composer.phar
	@if [ ! -d "prestashop/prestashop-${PS_VERSION}" ]; then \
		git clone --depth 1 --branch ${PS_VERSION} https://github.com/PrestaShop/PrestaShop.git prestashop/prestashop-${PS_VERSION} > /dev/null; \
		if [ "${PS_VERSION}" != "1.6.1.24" ]; then \
			./composer.phar -d ./prestashop/prestashop-${PS_VERSION} install; \
    fi \
	fi;

# target: lint (or docker-lint)                                - Lint the code and expose errors
.PHONY: lint docker-lint
lint: php-cs-fixer php-lint
docker-lint: docker-php-cs-fixer docker-php-lint

# target: lint-fix (or docker-lint-fix)                        - Automatically fix the linting errors
.PHONY: lint-fix docker-lint-fix fix
fix: lint-fix
lint-fix: php-cs-fixer-fix
docker-lint-fix: docker-php-cs-fixer-fix

# target: php-cs-fixer (or docker-php-cs-fixer)                - Lint the code and expose errors
.PHONY: php-cs-fixer docker-php-cs-fixer  
php-cs-fixer: tests/vendor
	@php-cs-fixer fix --dry-run --diff;
docker-php-cs-fixer: tests/vendor
	@$(call in_docker,make,lint)

# target: php-cs-fixer-fix (or docker-php-cs-fixer-fix)        - Lint the code and fix it
.PHONY: php-cs-fixer-fix docker-php-cs-fixer-fix
php-cs-fixer-fix: tests/vendor
	@php-cs-fixer fix
docker-php-cs-fixer-fix: tests/vendor
	@$(call in_docker,make,lint-fix)

# target: php-lint (or docker-php-lint)                        - Lint the code with the php linter
.PHONY: php-lint docker-php-lint
php-lint:
	@find . -type f -name '*.php' -not -path "./vendor/*" -not -path "./tests/*" -not -path "./prestashop/*" -print0 | xargs -0 -n1 php -l -n | (! grep -v "No syntax errors" );
	@echo "php $(shell php -r 'echo PHP_VERSION;') lint passed";
docker-php-lint:
	@$(call in_docker,make,php-lint)

# target: phpunit (or docker-phpunit)                          - Run phpunit tests
.PHONY: phpunit docker-phpunit
phpunit: tests/vendor
	phpunit --configuration=./tests/phpunit.xml;
docker-phpunit: tests/vendor
	@$(call in_docker,make,phpunit)

# target: phpunit-cov (or docker-phpunit-cov)                  - Run phpunit with coverage and allure
.PHONY: phpunit-cov docker-phpunit-cov
phpunit-cov: tests/vendor
	php -dxdebug.mode=coverage phpunit --coverage-html ./coverage-reports/coverage-html --configuration=./tests/phpunit-cov.xml;
docker-phpunit-cov: tests/vendor
	@$(call in_docker,make,phpunit-cov)

# target: phpstan (or docker-phpstan)                          - Run phpstan
.PHONY: phpstan docker-phpstan
phpstan: tests/vendor prestashop/prestashop-${PS_VERSION}
	phpstan analyse --memory-limit=-1 --configuration=./tests/phpstan/phpstan-local.neon;
docker-phpstan:
	@$(call in_docker,/usr/bin/phpstan,analyse --memory-limit=-1 --configuration=./tests/phpstan/phpstan-docker.neon)


############
# PHP-SCOPER

#VENDOR_DIRS = guzzlehttp league prestashopcorp
PHP_SCOPER_VENDOR_DIRS = $(shell cat scoper.inc.php | grep 'dirScoped =' | sed 's/^.*\$dirScoped = \[\(.*\)\].*/\1/' | sed "s/[' ,]\+/ /g")
PHP_SCOPER_OUTPUT_DIR := vendor-scoped
PHP_SCOPER_VERSION := 0.18.11

php-scoper.phar:
	curl -s -f -L -O "https://github.com/humbug/php-scoper/releases/download/${PHP_SCOPER_VERSION}/php-scoper.phar"
	chmod +x php-scoper.phar

.PHONY: php-scoper-add-prefix
php-scoper-add-prefix: php-scoper.phar scoper.inc.php vendor
	./php-scoper.phar add-prefix --output-dir ${PHP_SCOPER_OUTPUT_DIR} --force --quiet
	ls ${PHP_SCOPER_OUTPUT_DIR}
	#for d in ${VENDOR_DIRS}; do rm -rf ./vendor/$$d && mv ./${SCOPED_DIR}/$$d ./vendor/; done;
	$(foreach DIR,$(PHP_SCOPER_VENDOR_DIRS), rm -rf "./vendor/${DIR}" && mv "./${PHP_SCOPER_OUTPUT_DIR}/${DIR}" ./vendor/;)
	rmdir "./${PHP_SCOPER_OUTPUT_DIR}"

.PHONY: php-scoper-dump-autoload
php-scoper-dump-autoload:
	./composer.phar dump-autoload --classmap-authoritative

.PHONY: php-scoper-fix-autoload
php-scoper-fix-autoload:
	php fix-autoload.php

.PHONY: php-scoper
php-scoper: php-scoper-add-prefix php-scoper-dump-autoload php-scoper-fix-autoload

#######
# TOOLS

# php-cs-fixer: COMPOSER_FILE := composer56.json
# php-cs-fixer: tests/vendor
# 	PHP_CS_FIXER_IGNORE_ENV=1 php ./tests/vendor/bin/php-cs-fixer fix --using-cache=no
#	vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --diff-format udiff

# target: autoindex                                            - Automatically add index.php to each folder (fix for misconfigured servers)
autoindex: tests/vendor
	php ./tests/vendor/bin/autoindex prestashop:add:index "${WORKDIR}"

# target: header-stamp                                         - Add header stamp to files
header-stamp: tests/vendor
	php ./tests/vendor/bin/header-stamp --target="${WORKDIR}" --license="assets/afl.txt" --exclude=".github,node_modules,vendor,tests,_dev"

define replace_version
	echo "Setting up version: ${VERSION}..."
	sed -i.bak -e "s/\(VERSION = \).*/\1\'${2}\';/" ${1}/${MODULE_NAME}.php
	sed -i.bak -e "s/\($this->version = \).*/\1\'${2}\';/" ${1}/${MODULE_NAME}.php
	sed -i.bak -e "s|\(<version><!\[CDATA\[\)[0-9a-z.-]\{1,\}]]></version>|\1${2}]]></version>|" ${1}/config.xml
	if [ -f "${1}/_dev/package.json" ]; then \
		sed -i.bak -e "s/\(\"version\"\: \).*/\1\"${2}\",/" "${1}/_dev/package.json"; \
		rm -f "${1}/_dev/package.json.bak"; \
	fi
	rm -f ${1}/${MODULE_NAME}.php.bak ${1}/config.xml.bak
endef

define zip_it
	$(eval TMP_DIR := $(shell mktemp -d))
	mkdir -p ${TMP_DIR}/${MODULE_NAME};
	cp -r $(shell cat .zip-contents) ${TMP_DIR}/${MODULE_NAME};
	WORKDIR=${TMP_DIR} make autoindex
	cp $1 ${TMP_DIR}/${MODULE_NAME}/config/config.yml
	$(call replace_version,${TMP_DIR}/${MODULE_NAME},${SEM_VERSION})
	cd ${TMP_DIR} && zip -9 -r $2 ./${MODULE_NAME};
	mv ${TMP_DIR}/$2 ./dist;
	rm -rf ${TMP_DIR};
endef

define in_docker
	docker run \
	--rm \
	--workdir /var/www/html/modules/${MODULE_NAME} \
	--volume $(shell pwd):/var/www/html/modules/${MODULE_NAME}:rw \
	--entrypoint $1 ${TESTING_IMAGE} $2
endef
