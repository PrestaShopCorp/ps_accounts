PHP = $(shell which php 2> /dev/null)
DOCKER = $(shell docker ps 2> /dev/null)
NPM = $(shell which npm 2> /dev/null)
YARN = $(shell which yarn 2> /dev/null)
MODULE ?= $(shell basename ${PWD})
CURRENT_UID := $(shell id -u)
CURRENT_GID := $(shell id -g)

default: bundle

help:
	@egrep "^# target" Makefile

clean:
	git -c core.excludesfile=/dev/null clean -X -d -f

##########################################################
# target: version

VERSION ?= 5.2.0#$(shell git describe --tags | sed 's/^v//')

version:
	@echo "...$(VERSION)..."
	sed -i -e "s/\(VERSION = \).*/\1\'${VERSION}\';/" ps_accounts.php
	sed -i -e "s/\($this->version = \).*/\1\'${VERSION}\';/" ps_accounts.php
	sed -i -e 's/\(<version><!\[CDATA\[\)[0-9a-z\.\-]\{1,\}.*\]\]><\/version>/\1'${VERSION}']]><\/version>/' config.xml
	sed -i -e "s/\(\"version\"\: \).*/\1\"${VERSION}\",/" ./_dev/package.json

##########################################################
# target: tests

tests: test-back test-front lint-back
test-back: lint-back phpstan phpunit
lint-back:
	vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --diff-format udiff

check-docker:
ifndef DOCKER
    $(error "DOCKER is unavailable on your system")
endif

##########################################################
# target: phpstan

PHPSTAN_VERSION ?= 0.12
PS_VERSION ?= latest #1.6.1.21|1.7.7.1|latest
NEON_FILE ?= phpstan-PS-1.7.neon #phpstan-PS-1.6.neon

phpstan: check-docker
	docker pull phpstan/phpstan:${PHPSTAN_VERSION}
	docker pull prestashop/prestashop:${PS_VERSION}
	docker run --rm -d -v ps-volume:/var/www/html --entrypoint /bin/sleep --name test-phpstan prestashop/prestashop:${PS_VERSION} 2s
	docker run --rm --volumes-from test-phpstan \
	  -v ${PWD}:/web/module \
	  -e _PS_ROOT_DIR_=/var/www/html \
	  --workdir=/web/module \
	  phpstan/phpstan:${PHPSTAN_VERSION} analyse \
	  --memory-limit=-1 \
	  --configuration=/web/module/tests/phpstan/${NEON_FILE}
	docker volume rm ps-volume

##########################################################
# target: php-unit

DOCKER_INTERNAL ?= 1.7 # 1.7|8|nightly
CONTAINER_INSTALL_DIR="/var/www/html/modules/ps_accounts"

phpunit-pull:
	docker pull prestashop/docker-internal-images:${DOCKER_INTERNAL}

phpunit-start:
	@DOCKER_INTERNAL=${DOCKER_INTERNAL} docker-compose up -d
	@echo phpunit started

phpunit-stop:
	@DOCKER_INTERNAL=${DOCKER_INTERNAL} docker-compose down
	@echo phpunit stopped

phpunit-restart: phpunit-stop phpunit-start

phpunit-module-config:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit \
		sh -c "if [ ! -f ./config/config.yml ]; then cp ./config/config.yml.dist ./config/config.yml; fi"

phpunit-module-version:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit \
		sh -c "echo \"Module v\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\""

phpunit-module-install: phpunit-module-config phpunit-module-version
	@docker exec phpunit sh -c "if [ -f ./bin/console ]; then php -d memory_limit=-1 ./bin/console prestashop:module install ps_accounts; fi"
	@docker exec phpunit sh -c "if [ ! -f ./bin/console ]; then php -d memory_limit=-1 ./modules/ps_accounts/tests/install-module.php; fi"

phpunit-permissions:
	@docker exec phpunit sh -c "if [ -d ./var ]; then chown -R www-data:www-data ./var; fi"
	@docker exec phpunit sh -c "if [ -d ./cache ]; then chown -R www-data:www-data ./cache; fi" # PS1.6
	@docker exec phpunit sh -c "if [ -d ./log ]; then chown -R www-data:www-data ./log; fi" # PS1.6

phpunit-run-unit: phpunit-permissions
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit ./vendor/bin/phpunit --testsuite unit

phpunit-run-feature: phpunit-permissions
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit ./vendor/bin/phpunit --testsuite feature

phpunit-xdebug:
	-@docker exec phpunit sh -c "docker-php-ext-enable xdebug"

phpunit-delay-5:
	@echo waiting 5 seconds
	@sleep 5

phpunit: phpunit-pull phpunit-restart phpunit-delay-5 phpunit-module-install phpunit-run-feature phpunit-run-unit
	@echo phpunit passed

phpunit-dev: phpunit-pull phpunit-restart phpunit-delay-5 phpunit-module-install phpunit-permissions
	@echo phpunit container is ready

vendor/phpunit/phpunit:
	./composer.phar install

test-front:
	npm --prefix=./_dev run lint

##########################################################
# target: fix-lint

fix-lint: vendor/bin/php-cs-fixer
	vendor/bin/php-cs-fixer fix --using-cache=no
	npm --prefix=./_dev run lint --fix

vendor/bin/php-cs-fixer:
	./composer.phar install

##########################################################
# target: php-scoper

#VENDOR_DIRS = guzzlehttp league prestashopcorp
VENDOR_DIRS = $(shell cat scoper.inc.php | grep 'dirScoped =' | sed 's/^.*\$dirScoped = \[\(.*\)\].*/\1/' | sed "s/[' ,]\+/ /g")
SCOPED_DIR := vendor-scoped
COMPOSER_OPTIONS ?= --prefer-dist -o --quiet

php-scoper-list:
	@echo "${VENDOR_DIRS}"

php-scoper-pull:
	docker pull humbugphp/php-scoper:latest

php-scoper-add-prefix: scoper.inc.php vendor
	docker run -v ${PWD}:/input -w /input -u ${CURRENT_UID}:${CURRENT_GID} \
		humbugphp/php-scoper:latest add-prefix --output-dir ${SCOPED_DIR} --force --quiet
	#for d in ${VENDOR_DIRS}; do rm -rf ./vendor/$$d && mv ./${SCOPED_DIR}/$$d ./vendor/; done;
	$(foreach DIR,$(VENDOR_DIRS), rm -rf "./vendor/${DIR}" && mv "./${SCOPED_DIR}/${DIR}" ./vendor/;)
	rmdir "./${SCOPED_DIR}"

php-scoper-dump-autoload:
	./composer.phar dump-autoload --classmap-authoritative

php-scoper-fix-autoload:
	php fix-autoload.php

php-scoper: php-scoper-add-prefix php-scoper-dump-autoload php-scoper-fix-autoload

.PHONY: vendor
vendor: composer.phar
	rm -rf ./vendor && ./composer.phar install ${COMPOSER_OPTIONS}

##########################################################

BUNDLE_ENV ?= # ex: local|preprod|prod
BUNDLE_ZIP ?= # ex: ps_accounts_flavor.zip

bundle: php-scoper config/config.yml vendor-tools
	@./scripts/bundle-module.sh "${BUNDLE_ZIP}" "${BUNDLE_ENV}"

build-front:
ifndef YARN
    $(error "YARN is unavailable on your system, try `npm i -g yarn`")
endif
	yarn --cwd ./_dev --frozen-lockfile
	yarn --cwd ./_dev build

.PHONY: vendor-tools
vendor-tools: composer.phar
	./composer.phar -d ./tools/ install

composer.phar:
ifndef PHP
    $(error "PHP is unavailable on your system")
endif
	./scripts/composer-install.sh

