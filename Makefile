PHP = $(shell which php 2> /dev/null)
DOCKER = $(shell docker ps 2> /dev/null)
NPM = $(shell which npm 2> /dev/null)
COMPOSER = ${PHP} ./composer.phar
DOCKER_COMPOSE := $(shell which docker) compose
MODULE ?= $(shell basename ${PWD})
CURRENT_UID := $(shell id -u)
CURRENT_GID := $(shell id -g)
WORKDIR ?= .
TESTING_IMAGE_TAG ?= base-8.2-fpm-alpine
TESTING_IMAGE ?= prestashop/prestashop-flashlight:${TESTING_IMAGE_TAG}

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

##########
# PLATFORM

PLATFORM_REPO ?= prestashop/prestashop-flashlight
PLATFORM_TAG ?= 8.1.5-7.4
PLATFORM_IMAGE ?= ${PLATFORM_REPO}:${PLATFORM_TAG}
PLATFORM_COMPOSE_FILE ?= docker-compose.flashlight.yml

COMPOSER_FILE ?= composer.json
.PHONY: tests/vendor
tests/vendor: composer.phar
#	rm -rf ./tests/vendor
	env COMPOSER=${COMPOSER_FILE} ${COMPOSER} install --working-dir=./tests/ --quiet

CONTAINER_INSTALL_DIR="/var/www/html/modules/ps_accounts"

platform-pull:
	docker pull ${PLATFORM_IMAGE}

platform-start:
	@PLATFORM_IMAGE=${PLATFORM_IMAGE} ${DOCKER_COMPOSE} -f ${PLATFORM_COMPOSE_FILE} up -d --wait
	@echo phpunit started

platform-stop:
	@PLATFORM_IMAGE=${PLATFORM_IMAGE} ${DOCKER_COMPOSE} -f ${PLATFORM_COMPOSE_FILE} down
	@echo phpunit stopped

platform-restart: platform-stop platform-start

config.php:
	cp ./config.dist.php ./config.php

platform-module-version:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit \
		sh -c "echo \"installing module: [\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/v\1/'\`]\""

platform-phpstan-config:
	@echo "installing neon file: [${NEON_FILE}]"
	@docker exec -w ${CONTAINER_INSTALL_DIR}/tests phpunit \
		sh -c "if [ -f ./phpstan/${NEON_FILE} ]; then cp ./phpstan/${NEON_FILE} ./phpstan/phpstan.neon; fi"

platform-module-install: tests/vendor platform-phpstan-config config.php platform-module-version
	@docker exec phpunit sh -c "if [ -f ./bin/console ]; then php -d memory_limit=-1 ./bin/console prestashop:module install ps_accounts; fi"
	@docker exec phpunit sh -c "if [ ! -f ./bin/console ]; then php -d memory_limit=-1 ./modules/ps_accounts/tests/install-module.php; fi"

platform-fix-permissions:
	@docker exec phpunit sh -c "if [ -d ./var ]; then chown -R www-data:www-data ./var; fi"
	@docker exec phpunit sh -c "if [ -d ./cache ]; then chown -R www-data:www-data ./cache; fi" # PS1.6
	@docker exec phpunit sh -c "if [ -d ./log ]; then chown -R www-data:www-data ./log; fi" # PS1.6
	#@docker exec -u root phpunit sh -c "chgrp -R www-data ${CONTAINER_INSTALL_DIR}"
	#@docker exec -u root phpunit sh -c "find ${CONTAINER_INSTALL_DIR} -type d -exec chmod g+r,g+w,g+x {} \;"
	#@docker exec -u root phpunit sh -c "find ${CONTAINER_INSTALL_DIR} -type f -exec chmod g+r,g+w {} \;"


#platform-status:
#	COMPOSER=composer71.json ./composer.phar outdated --locked -m --working-dir=./tests/

#platform-status:
#	COMPOSER=composer71.json ./composer.phar outdated --locked -m --working-dir=./tests/

#phpunit-xdebug:
#	-@docker exec phpunit sh -c "docker-php-ext-enable xdebug"

# FIXME: set neon file for platform
define build-platform
	$(eval target = $1)
	$(eval repo = $2)
	$(eval tag = $3)
	$(eval composer = $4)
	$(eval neonfile = $5)

	$(eval repo = $(if $(repo:-=),$(repo),${PLATFORM_REPO}))
	$(eval tag = $(if $(tag:-=),$(tag),$(shell echo $(target) | sed 's/^platform\(-[a-z0-9]*\)\?-//')))
	$(eval composer = $(if $(composer:-=),$(composer),${COMPOSER_FILE}))
	$(eval neonfile = $(if $(neonfile:-=),$(neonfile),${NEON_FILE}))

	PLATFORM_REPO=$(repo) \
	PLATFORM_TAG=$(tag) \
	PLATFORM_COMPOSE_FILE=.docker/$(shell echo 'docker-compose.'$(repo)'.yml' | sed 's/\//@/') \
	COMPOSER_FILE=${composer} \
	NEON_FILE=${neonfile} \
	$(MAKE) platform-init
endef

# FIXME: check for PrestaShop & DB coming alive
platform-is-alive:
	sleep 0

platform-init: platform-pull platform-restart platform-is-alive platform-module-install platform-fix-permissions
	@echo platform container is ready

##################
# PLATFORM PRESETS

# example:
# major x php range x vendor
# PS16  | 5.6 - 7.1 | vendor56
# PS17  | 7.1 - 8.0 | vendor71
# PS80  | 7.4 - 8.0 | vendor71
# PS90  | 8.O - *   | vendor80

platform-1.6.1.24-5.6-fpm-stretch:
	$(call build-platform,$@,,,composer56.json,phpstan\-PS\-1.6.neon)

platform-1.6.1.24-7.1:
	$(call build-platform,$@,,,composer71.json,phpstan\-PS\-1.6.neon)

platform-1.7.5.2-7.1:
	$(call build-platform,$@,,,composer71.json)

platform-1.7.7.8-7.1:
	$(call build-platform,$@,,,composer71.json)

platform-1.7.8.5-7.4:
	$(call build-platform,$@)

platform-8.1.5-7.4:
	$(call build-platform,$@)

platform-8.2.0-8.1:
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

phpunit-display-logs:
	-@docker exec phpunit sh -c "if [ -f ./bin/console ]; then cat var/logs/ps_accounts-$(shell date --iso); fi"
	-@docker exec phpunit sh -c "if [ ! -f ./bin/console ]; then cat log/ps_accounts-$(shell date --iso); fi"

phpunit: phpunit-run-unit phpunit-run-feature

#########
# PHPSTAN

NEON_FILE ?= phpstan-PS-1.7.neon
phpstan:
	@docker exec -w ${CONTAINER_INSTALL_DIR}/tests -e _PS_ROOT_DIR_=${CONTAINER_INSTALL_DIR}/../.. \
	  phpunit ./vendor/bin/phpstan analyse \
	  --autoload-file=bootstrap.php \
	  --memory-limit=-1 \
	  --configuration=./phpstan/phpstan.neon

##############
# HEADER-STAMP

#header-stamp-test:
#	@docker exec -w ${CONTAINER_INSTALL_DIR} \
#	phpunit ./tests/vendor/bin/header-stamp \
#	--target="${WORKDIR}" \
#	--license=./tests/vendor/prestashop/header-stamp/assets/afl.txt \
#	--exclude=.github,node_modules,vendor,dist,tests,e2e,e2e-env,_dev \
#	--dry-run
#
## 1.6.1.24-5.6-fpm-stretch
#header-stamp:
#	@docker exec -w ${CONTAINER_INSTALL_DIR} \
#	phpunit ./tests/vendor/bin/header-stamp \
#	--target="${WORKDIR}" \
#	--license=./tests/vendor/prestashop/header-stamp/assets/afl.txt \
#	--exclude=.github,node_modules,vendor,dist,tests,e2e,e2e-env,_dev

#phpstan16: NEON_FILE := phpstan-PS-1.6.neon
#phpstan16: phpstan

##############
# PHP-CS-FIXER

platform-php-cs-fixer-test:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit ./tests/vendor/bin/php-cs-fixer fix --dry-run --diff --diff-format udiff
platform-php-cs-fixer:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit ./tests/vendor/bin/php-cs-fixer fix --using-cache=no

#################
# TESTING TARGETS

phpunit-1.6.1.24-5.6-fpm-stretch: platform-1.6.1.24-5.6-fpm-stretch phpunit
phpunit-1.6.1.24-7.1:             platform-1.6.1.24-7.1             phpunit
phpunit-1.7.7.8-7.1:              platform-1.7.7.8-7.1              phpunit
phpunit-1.7.8.5-7.4:              platform-1.7.8.5-7.4              phpunit
phpunit-8.1.5-7.4:                platform-8.1.5-7.4                phpunit
phpunit-nightly:                  platform-nightly                  phpunit
#phpunit-internal-1.6:             platform-internal-1.6 phpunit

#"latest", "1.7.6.5", "1.6.1.21"
phpstan-1.6.1.24-7.1: platform-1.6.1.24-7.1 phpstan
phpstan-8.1.5-7.4:    platform-8.1.5-7.4    phpstan

#php-cs-fixer-test-1.6.1.24-5.6-fpm-stretch: platform-1.6.1.24-5.6-fpm-stretch platform-php-cs-fixer-test
#php-cs-fixer-1.6.1.24-5.6-fpm-stretch: platform-1.6.1.24-5.6-fpm-stretch platform-php-cs-fixer

############
# PHP-SCOPER

#VENDOR_DIRS = guzzlehttp league prestashopcorp
#PHP_SCOPER_VENDOR_DIRS = $(shell cat scoper.inc.php | grep 'dirScoped =' | sed 's/^.*\$dirScoped = \[\(.*\)\].*/\1/' | sed "s/[' ,]\+/ /g")
PHP_SCOPER_VENDOR_DIRS = $(shell cat .dir-scoped)
PHP_SCOPER_OUTPUT_DIR := vendor-scoped
PHP_SCOPER_VERSION := 0.18.11

${WORKDIR}/php-scoper.phar:
	curl -s -f -L -O "https://github.com/humbug/php-scoper/releases/download/${PHP_SCOPER_VERSION}/php-scoper.phar"
	chmod +x ${WORKDIR}/php-scoper.phar

php-scoper-list:
	@echo "${PHP_SCOPER_VENDOR_DIRS}"

#php-scoper-pull:
#	docker pull humbugphp/php-scoper:${PHP_SCOPER_VERSION}

php-scoper-add-prefix: scoper.inc.php vendor-clean vendor ${WORKDIR}/php-scoper.phar
	#${WORKDIR}/php-scoper.phar add-prefix --output-dir ${PHP_SCOPER_OUTPUT_DIR} --force --quiet
	#docker run -v ${PWD}:/input -w /input -u ${CURRENT_UID}:${CURRENT_GID} \
	#	humbugphp/php-scoper:${PHP_SCOPER_VERSION} add-prefix --output-dir ${PHP_SCOPER_OUTPUT_DIR} --force --quiet
	$(call in_docker,${WORKDIR}/php-scoper.phar add-prefix --output-dir ${PHP_SCOPER_OUTPUT_DIR} --force --quiet)
	#for d in ${VENDOR_DIRS}; do rm -rf ./vendor/$$d && mv ./${SCOPED_DIR}/$$d ./vendor/; done;
	$(foreach DIR,$(PHP_SCOPER_VENDOR_DIRS), rm -rf "./vendor/${DIR}" && mv "./${PHP_SCOPER_OUTPUT_DIR}/${DIR}" ./vendor/${DIR};)
	if [ ! -z ${PHP_SCOPER_OUTPUT_DIR} ]; then rm -rf "./${PHP_SCOPER_OUTPUT_DIR}"; fi


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

bundle: config.php php-scoper build-front
	@./scripts/bundle-module.sh "${BUNDLE_ZIP}" "${BUNDLE_ENV}"

bundle-prod: php-scoper config.php.prod build-front
	@./scripts/bundle-module.sh "ps_accounts.zip" "prod"

bundle-preprod: php-scoper config.php.preprod build-front
	@./scripts/bundle-module.sh "ps_accounts_preprod.zip" "preprod"

define build_front
	rm -f ./views/js/app.*.js
	rm -f ./views/css/app.*.css
	pnpm --filter ./_dev install --frozen-lockfile --ignore-scripts
	pnpm --filter ./_dev build
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

#################
# php-cs-fixer

PHP_CS_FIXER_EXTRA_OPTS ?=
php-cs-fixer: COMPOSER_FILE := composer56.json
php-cs-fixer: tests/vendor
	PHP_CS_FIXER_IGNORE_ENV=1 ${PHP} ./tests/vendor/bin/php-cs-fixer fix \
		${PHP_CS_FIXER_EXTRA_OPTS} --using-cache=no

php-cs-fixer-test: COMPOSER_FILE := composer56.json
php-cs-fixer-test: PHP_CS_FIXER_EXTRA_OPTS := --dry-run --diff --diff-format udiff
php-cs-fixer-test: tests/vendor php-cs-fixer

#################
# autoindex

autoindex: COMPOSER_FILE := composer56.json
autoindex: tests/vendor
	${PHP} ./tests/vendor/bin/autoindex prestashop:add:index "${WORKDIR}"

#################
# header-stamp

HEADER_STAMP_EXTRA_OPTS ?=
header-stamp: COMPOSER_FILE := composer56.json
header-stamp: tests/vendor
	${PHP} -d error_reporting=0 ./tests/vendor/bin/header-stamp \
		--target="${WORKDIR}" ${HEADER_STAMP_EXTRA_OPTS} \
		--license="./tests/vendor/prestashop/header-stamp/assets/afl.txt" \
		--exclude=".github,node_modules,vendor,dist,tests,e2e,e2e-env,_dev"

header-stamp-test: COMPOSER_FILE := composer56.json
header-stamp-test: HEADER_STAMP_EXTRA_OPTS := --dry-run
header-stamp-test: tests/vendor header-stamp

##########################################################
COMPOSER_OPTIONS ?= --prefer-dist -o --no-dev --quiet

vendor-clean:
	rm -rf ./vendor

.PHONY: vendor
vendor: composer.phar
	${COMPOSER} install ${COMPOSER_OPTIONS}

define in_docker
	docker run \
	--rm \
	--user ${CURRENT_UID}:${CURRENT_GID} \
	--env _PS_ROOT_DIR_=/var/www/html \
	--workdir /var/www/html/modules/${MODULE_NAME} \
	--volume $(shell cd ${WORKDIR} && pwd):/var/www/html/modules/${MODULE_NAME}:rw \
	${TESTING_IMAGE} $1
endef
