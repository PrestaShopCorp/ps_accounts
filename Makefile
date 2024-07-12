PHP = $(shell which php 2> /dev/null)
DOCKER = $(shell docker ps 2> /dev/null)
NPM = $(shell which npm 2> /dev/null)
YARN = $(shell which yarn 2> /dev/null)
COMPOSER = ${PHP} ./composer.phar
DOCKER_COMPOSE := $(shell which docker) compose
MODULE ?= $(shell basename ${PWD})
CURRENT_UID := $(shell id -u)
CURRENT_GID := $(shell id -g)

default: bundle

help:
	@egrep "^# target" Makefile

##########################################################
# target: version

VERSION ?= $(shell git describe --tags | sed 's/^v//' | cut -d'-' -f1)

version:
	@echo "Setting up version number : $(VERSION)..."
	@sed -i -e "s/\(VERSION = \).*/\1\'${VERSION}\';/" ps_accounts.php
	@sed -i -e "s/\($this->version = \).*/\1\'${VERSION}\';/" ps_accounts.php
	@sed -i -e 's/\(<version><!\[CDATA\[\)[0-9a-z\.\-]\{1,\}.*\]\]><\/version>/\1'${VERSION}']]><\/version>/' config.xml
	@sed -i -e "s/\(\"version\"\: \).*/\1\"${VERSION}\",/" ./_dev/package.json

##########################################################
# target: phpstan

#PHPSTAN_VERSION ?= 0.12
#PS_VERSION ?= latest
#NEON_FILE ?= phpstan-PS-1.7.neon
#
#phpstan:
#	-docker volume rm ps-volume
#	docker pull phpstan/phpstan:${PHPSTAN_VERSION}
#	docker pull prestashop/prestashop:${PS_VERSION}
#	docker run --rm -d -v ps-volume:/var/www/html --entrypoint /bin/sleep --name test-phpstan-${PS_VERSION} prestashop/prestashop:${PS_VERSION} 2s
#	docker run --rm --volumes-from test-phpstan-${PS_VERSION} \
#	  -v ${PWD}:/web/module \
#	  -e _PS_ROOT_DIR_=/var/www/html \
#	  --workdir=/web/module \
#	  phpstan/phpstan:${PHPSTAN_VERSION} analyse \
#	  --memory-limit=-1 \
#	  --configuration=/web/module/tests/phpstan/${NEON_FILE}
##	docker volume rm ps-volume
#
#phpstan16: PS_VERSION = 1.6.1.21
#phpstan16: NEON_FILE = phpstan-PS-1.6.neon
#phpstan16: phpstan

##########
# PLATFORM

PLATFORM_REPO ?= prestashop/prestashop-flashlight
PLATFORM_REPO_TAG ?= 8.1.5-7.4
PLATFORM_IMAGE ?= ${PLATFORM_REPO}:${PHPUNIT_TAG}
PLATFORM_COMPOSE_FILE ?= docker-compose.flashlight.yml

COMPOSER_FILE ?= composer.json
.PHONY: tests/vendor
tests/vendor:
#	rm -rf ./tests/vendor
	env COMPOSER=${COMPOSER_FILE} ${COMPOSER} install --working-dir=./tests/

CONTAINER_INSTALL_DIR="/var/www/html/modules/ps_accounts"

platform-pull:
	docker pull ${PLATFORM_IMAGE}

platform-start:
	@PLATFORM_IMAGE=${PLATFORM_IMAGE} ${DOCKER_COMPOSE} -f ${PLATFORM_COMPOSE_FILE} up -d
	@echo phpunit started

platform-stop:
	@PLATFORM_IMAGE=${PLATFORM_IMAGE} ${DOCKER_COMPOSE} -f ${PLATFORM_COMPOSE_FILE} down
	@echo phpunit stopped

platform-restart: platform-stop platform-start

platform-module-config:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit \
		sh -c "if [ ! -f ./config/config.yml ]; then cp ./config/config.yml.dist ./config/config.yml; fi"

platform-module-version:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit \
		sh -c "echo \"Module v\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\""

platform-module-install: tests/vendor platform-module-config platform-module-version
	@docker exec phpunit sh -c "if [ -f ./bin/console ]; then php -d memory_limit=-1 ./bin/console prestashop:module install ps_accounts; fi"
	@docker exec phpunit sh -c "if [ ! -f ./bin/console ]; then php -d memory_limit=-1 ./modules/ps_accounts/tests/install-module.php; fi"

platform-fix-permissions:
	@docker exec phpunit sh -c "if [ -d ./var ]; then chown -R www-data:www-data ./var; fi"
	@docker exec phpunit sh -c "if [ -d ./cache ]; then chown -R www-data:www-data ./cache; fi" # PS1.6
	@docker exec phpunit sh -c "if [ -d ./log ]; then chown -R www-data:www-data ./log; fi" # PS1.6

#phpunit-xdebug:
#	-@docker exec phpunit sh -c "docker-php-ext-enable xdebug"

define build-platform
	$(eval target = $1)
	$(eval repo = $2)
	$(eval tag = $3)
	$(eval composer = $4)

	$(eval repo = $(if $(repo:-=),$(repo),${PLATFORM_REPO}))
	$(eval tag = $(if $(tag:-=),$(tag),$(shell echo $(target) | sed 's/^platform\(-[a-z0-9]*\)\?-//')))
	$(eval composer = $(if $(composer:-=),$(composer),${COMPOSER_FILE}))

	PLATFORM_REPO=$(repo) \
	PHPUNIT_TAG=$(tag) \
	PLATFORM_COMPOSE_FILE=$(shell echo 'docker-compose.'$(repo)'.yml' | sed 's/\//@/') \
	COMPOSER_FILE=${composer} \
	$(MAKE) platform-init
endef

# FIXME: check for PrestaShop & DB coming alive
platform-is-alive:
	sleep 10

platform-init: platform-pull platform-restart platform-is-alive platform-module-install platform-fix-permissions
	@echo platform container is ready

##################
# PLATFORM PRESETS

platform-1.6.1.24-5.6-fpm-stretch:
	$(call build-platform,$@,,,composer56.json)

platform-1.6.1.24-7.1:
	$(call build-platform,$@,,,composer71.json)

platform-1.7.7.8-7.1:
	$(call build-platform,$@,,,composer71.json)

platform-1.7.8.5-7.4:
	$(call build-platform,$@)

platform-8.1.5-7.4:
	$(call build-platform,$@)

platform-nightly:
	$(call build-platform,$@)

platform-internal-1.6:
	@docker container stop ps_accounts_mysql_1
	$(call build-platform,$@,"prestashop/docker-internal-images",,composer71.json)

#########
# PHPUNIT

phpunit-run-unit: platform-fix-permissions
	@docker exec -w ${CONTAINER_INSTALL_DIR}/tests phpunit ./vendor/bin/phpunit --testsuite unit

phpunit-run-feature: platform-fix-permissions
	@docker exec -w ${CONTAINER_INSTALL_DIR}/tests phpunit ./vendor/bin/phpunit --testsuite feature

phpunit-run-all: phpunit-run-unit phpunit-run-feature

REGEX_COMPAT_VOID := "s/\(function \(setUp\|tearDown\)()\)\(: void\)\?/\1/"
REGEX_COMPAT_TRAIT := "s/\#\?\(use \\\\DMS\\\\PHPUnitExtensions\\\\ArraySubset\\\\ArraySubsetAsserts;\)/\#\1/"
phpunit-fix-compat-php56:
	@echo "fixing compat for php56..."
	find ./tests -type f -name "TestCase.php" -exec sed -i -e ${REGEX_COMPAT_TRAIT} {} \;
	find ./tests -type f -name "TestCase.php" -exec sed -i -e ${REGEX_COMPAT_VOID} {} \;
	find ./tests/Unit -type f -name "*.php" -exec sed -i -e ${REGEX_COMPAT_VOID} {} \;
	find ./tests/Feature -type f -name "*.php" -exec sed -i -e ${REGEX_COMPAT_VOID} {} \;

phpunit-reset-compat-php56: REGEX_COMPAT_VOID := "s/\(function \(setUp\|tearDown\)()\)\(: void\)\?/\1: void/"
phpunit-reset-compat-php56: REGEX_COMPAT_TRAIT := "s/\#\?\(use \\\\DMS\\\\PHPUnitExtensions\\\\ArraySubset\\\\ArraySubsetAsserts;\)/\1/"
phpunit-reset-compat-php56: phpunit-fix-compat-php56

#########
# PHPSTAN

NEON_FILE = phpstan-PS-1.7.neon
phpstan:
	@docker exec -w ${CONTAINER_INSTALL_DIR}/tests -e _PS_ROOT_DIR_=${CONTAINER_INSTALL_DIR}/../.. \
	  phpunit ./vendor/bin/phpstan analyse \
	  --autoload-file=bootstrap.php \
	  --memory-limit=-1 \
	  --configuration=./phpstan/${NEON_FILE}

phpstan16: NEON_FILE := phpstan-PS-1.6.neon
phpstan16: phpstan

##############
# PHP-CS-FIXER

platform-php-cs-fixer-test:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit ./tests/vendor/bin/php-cs-fixer fix --dry-run --diff --diff-format udiff
platform-php-cs-fixer:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit ./tests/vendor/bin/php-cs-fixer fix --using-cache=no

#################
# TESTING TARGETS

#phpunit-internal-1.6:
#	@docker container stop ps_accounts_mysql_1
#	$(call phpunit-version,$@,"prestashop/docker-internal-images",,composer71.json)

phpunit-1.6.1.24-5.6-fpm-stretch: platform-1.6.1.24-5.6-fpm-stretch phpunit-fix-compat-php56 phpunit-run-all
phpunit-1.6.1.24-7.1:             platform-1.6.1.24-7.1 phpunit-run-all
phpunit-1.7.7.8-7.1:              platform-1.7.7.8-7.1 phpunit-run-all
phpunit-1.7.8.5-7.4:              platform-1.7.8.5-7.4 phpunit-run-all
phpunit-8.1.5-7.4:                platform-8.1.5-7.4 phpunit-run-all
phpunit-nightly:                  platform-nightly phpunit-run-all
#phpunit-internal-1.6:             platform-internal-1.6 phpunit-run-all

#"latest", "1.7.6.5", "1.6.1.21"
phpstan-1.6.1.24-7.1: platform-1.6.1.24-7.1 phpstan16
phpstan-1.7.7.8-7.1:  platform-1.7.7.8-7.1 phpstan

php-cs-fixer-test-1.6.1.24-5.6-fpm-stretch: platform-1.6.1.24-5.6-fpm-stretch platform-php-cs-fixer-test
php-cs-fixer-1.6.1.24-5.6-fpm-stretch: platform-1.6.1.24-5.6-fpm-stretch platform-php-cs-fixer

############
# PHP-SCOPER

#VENDOR_DIRS = guzzlehttp league prestashopcorp
PHP_SCOPER_VENDOR_DIRS = $(shell cat scoper.inc.php | grep 'dirScoped =' | sed 's/^.*\$dirScoped = \[\(.*\)\].*/\1/' | sed "s/[' ,]\+/ /g")
PHP_SCOPER_OUTPUT_DIR := vendor-scoped
PHP_SCOPER_VERSION := 0.18.11

php-scoper-list:
	@echo "${PHP_SCOPER_VENDOR_DIRS}"

php-scoper-pull:
	docker pull humbugphp/php-scoper:${PHP_SCOPER_VERSION}

php-scoper-add-prefix: scoper.inc.php vendor-clean vendor php-scoper-pull
	docker run -v ${PWD}:/input -w /input -u ${CURRENT_UID}:${CURRENT_GID} \
		humbugphp/php-scoper:${PHP_SCOPER_VERSION} add-prefix --output-dir ${PHP_SCOPER_OUTPUT_DIR} --force --quiet
	#for d in ${VENDOR_DIRS}; do rm -rf ./vendor/$$d && mv ./${SCOPED_DIR}/$$d ./vendor/; done;
	$(foreach DIR,$(PHP_SCOPER_VENDOR_DIRS), rm -rf "./vendor/${DIR}" && mv "./${PHP_SCOPER_OUTPUT_DIR}/${DIR}" ./vendor/;)
	rmdir "./${PHP_SCOPER_OUTPUT_DIR}"

php-scoper-dump-autoload:
	${COMPOSER} dump-autoload --classmap-authoritative

php-scoper-fix-autoload:
	php fix-autoload.php

php-scoper: php-scoper-add-prefix php-scoper-dump-autoload php-scoper-fix-autoload

##########
# BUNDLING

BUNDLE_ENV ?= # ex: local|preprod|prod
BUNDLE_ZIP ?= # ex: ps_accounts_flavor.zip
BUNDLE_VERSION ?= $(shell grep "<version>" config.xml | sed 's/^.*\([0-9]\+\.[0-9]\+\.[0-9]\+\).*/\1/')
BUNDLE_JS ?= views/js/app.${BUNDLE_VERSION}.js

bundle: php-scoper config/config.yml build-front
	@./scripts/bundle-module.sh "${BUNDLE_ZIP}" "${BUNDLE_ENV}"

bundle-prod: php-scoper config/config.yml.prod build-front
	@./scripts/bundle-module.sh "ps_accounts.zip" "prod"

bundle-preprod: php-scoper config/config.yml.preprod build-front
	@./scripts/bundle-module.sh "ps_accounts_preprod.zip" "preprod"

define build_front
	yarn --cwd ./_dev --frozen-lockfile
	yarn --cwd ./_dev build
endef

${BUNDLE_JS}:
	$(call build_front)

build-front: ${BUNDLE_JS}

composer.phar:
	./scripts/composer-install.sh

#clean:
#	git -c core.excludesfile=/dev/null clean -X -d -f

#######
# TOOLS

WORKDIR ?= ./

php-cs-fixer: COMPOSER_FILE := composer56.json
php-cs-fixer: tests/vendor
	PHP_CS_FIXER_IGNORE_ENV=1 ${PHP} ./tests/vendor/bin/php-cs-fixer fix --using-cache=no
#	vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --diff-format udiff

autoindex: COMPOSER_FILE := composer56.json
autoindex: tests/vendor
	${PHP} ./tests/vendor/bin/autoindex prestashop:add:index "${WORKDIR}"

header-stamp: COMPOSER_FILE := composer56.json
header-stamp: tests/vendor
	${PHP} ./vendor/bin/header-stamp --target="${WORKDIR}" --license="assets/afl.txt" --exclude=".github,node_modules,vendor,vendor,tests,_dev"

##########################################################
COMPOSER_OPTIONS ?= --prefer-dist -o --no-dev --quiet

vendor-clean:
	rm -rf ./vendor

.PHONY: vendor
vendor: composer.phar
	${COMPOSER} install ${COMPOSER_OPTIONS}

