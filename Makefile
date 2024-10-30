SHELL = /bin/bash -o pipefail
MODULE_NAME = ps_accounts
VERSION ?= $(shell git describe --tags 2> /dev/null || echo "v0.0.0")
SEM_VERSION ?= $(shell echo ${VERSION} | sed 's/^v//')
BRANCH_NAME ?= $(shell git rev-parse --abbrev-ref HEAD | sed -e 's/\//_/g')
PACKAGE ?= ${MODULE_NAME}-${VERSION}
WORKDIR ?= .

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
PLATFORM_REPO_TAG ?= 8.1.5-7.4
PLATFORM_IMAGE ?= ${PLATFORM_REPO}:${PHPUNIT_TAG}
PLATFORM_COMPOSE_FILE ?= docker-compose.flashlight.yml

COMPOSER_FILE ?= composer.json
.PHONY: tests/vendor
tests/vendor:
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

platform-module-config:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit \
		sh -c "if [ ! -f ./config/config.yml ]; then cp ./config/config.yml.dist ./config/config.yml; fi"

platform-module-version:
	@docker exec -w ${CONTAINER_INSTALL_DIR} phpunit \
		sh -c "echo \"installing module: [\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/v\1/'\`]\""

platform-phpstan-config:
	@echo "installing neon file: [${NEON_FILE}]"
	@docker exec -w ${CONTAINER_INSTALL_DIR}/tests phpunit \
		sh -c "if [ -f ./phpstan/${NEON_FILE} ]; then cp ./phpstan/${NEON_FILE} ./phpstan/phpstan.neon; fi"

platform-module-install: tests/vendor platform-phpstan-config platform-module-config platform-module-version
	-@docker exec phpunit sh -c "if [ -f ./bin/console ]; then php -d memory_limit=-1 ./bin/console prestashop:module install ps_accounts; fi"
	-@docker exec phpunit sh -c "if [ ! -f ./bin/console ]; then php -d memory_limit=-1 ./modules/ps_accounts/tests/install-module.php; fi"

platform-fix-permissions:
	@docker exec phpunit sh -c "if [ -d ./var ]; then chown -R www-data:www-data ./var; fi"
	@docker exec phpunit sh -c "if [ -d ./cache ]; then chown -R www-data:www-data ./cache; fi" # PS1.6
	@docker exec phpunit sh -c "if [ -d ./log ]; then chown -R www-data:www-data ./log; fi" # PS1.6

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
	PHPUNIT_TAG=$(tag) \
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

platform-1.6.1.24-5.6-fpm-stretch: phpunit-fix-compat-php56
	$(call build-platform,$@,,,composer56.json,phpstan\-PS\-1.6.neon)

platform-1.6.1.24-7.1:
	$(call build-platform,$@,,,composer71.json,phpstan\-PS\-1.6.neon)

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

phpunit-display-logs:
	-@docker exec phpunit sh -c "if [ -f ./bin/console ]; then cat var/logs/ps_accounts-$(shell date --iso); fi"
	-@docker exec phpunit sh -c "if [ ! -f ./bin/console ]; then cat log/ps_accounts-$(shell date --iso); fi"

phpunit: phpunit-run-unit phpunit-run-feature

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

NEON_FILE ?= phpstan-PS-1.7.neon
phpstan:
	@docker exec -w ${CONTAINER_INSTALL_DIR}/tests -e _PS_ROOT_DIR_=${CONTAINER_INSTALL_DIR}/../.. \
	  phpunit ./vendor/bin/phpstan analyse \
	  --autoload-file=bootstrap.php \
	  --memory-limit=-1 \
	  --configuration=./phpstan/phpstan.neon

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
phpstan-1.7.7.8-7.1:  platform-1.7.7.8-7.1  phpstan

php-cs-fixer-test-1.6.1.24-5.6-fpm-stretch: platform-1.6.1.24-5.6-fpm-stretch platform-php-cs-fixer-test
php-cs-fixer-1.6.1.24-5.6-fpm-stretch: platform-1.6.1.24-5.6-fpm-stretch platform-php-cs-fixer

############
# PHP-SCOPER

#VENDOR_DIRS = guzzlehttp league prestashopcorp
#PHP_SCOPER_VENDOR_DIRS = $(shell cat scoper.inc.php | grep 'dirScoped =' | sed 's/^.*\$dirScoped = \[\(.*\)\].*/\1/' | sed "s/[' ,]\+/ /g")
PHP_SCOPER_VENDOR_DIRS = $(shell cat .dir-scoped)
PHP_SCOPER_OUTPUT_DIR := vendor-scoped
PHP_SCOPER_VERSION := 0.18.11
BUNDLE_JS ?= ${WORKDIR}/views/js/app.${SEM_VERSION}.js
COMPOSER_OPTIONS ?= --prefer-dist -o --no-dev --quiet
BUILD_DEPENDENCIES = ${WORKDIR}/dist ${WORKDIR}/vendor ${WORKDIR}/tests/vendor ${WORKDIR}/_dev/node_modules/.modules.yaml ${WORKDIR}/vendor/.scoped
TOOLS = ${WORKDIR}/tests/vendor

export PATH := ${WORKDIR}/vendor/bin:${WORKDIR}/tests/vendor/bin:$(PATH)
export UID=$(id -u)
export GID=$(id -g)
export PHP_CS_FIXER_IGNORE_ENV = 1
export _PS_ROOT_DIR_ ?= ${PS_ROOT_DIR}

# target: (default)                                            - Build the module
default: build

# target: build                                                - Install dependencies and build assets
.PHONY: build
build: ${BUILD_DEPENDENCIES}

# target: help                                                 - Get help on this file
.PHONY: help
help:
	@echo -e "##\n# ${MODULE_NAME}:\n#  version: ${VERSION}\n#  branch:  ${BRANCH_NAME}\n##"
	@egrep "^# target" Makefile

# target: clean                                                - Clean up the repository (but keep you .npmrc)
.PHONY: clean
clean:
	git clean -fdX --exclude="!.npmrc" --exclude="!.env*"

# target: vendor-clean                                         - Remove composer dependencies
.PHONY: vendor-clean
vendor-clean:
	rm -rf ${WORKDIR}/vendor tests/vendor

# target: clean-deps                                           - Remove composer and npm dependencies
.PHONY: clean-deps
clean-deps: vendor-clean
	rm -rf ${WORKDIR}/_dev/node_modules

# target: zip                                                  - Make all zip bundles
.PHONY: zip
zip: zip-local zip-preprod zip-prod

# target: zip-local                                            - Bundle a local E2E compatible zip
.PHONY: zip-local
zip-local: ${BUILD_DEPENDENCIES}
	$(eval PKG_LOCAL := $(if $(filter main,$(BRANCH_NAME)),${PACKAGE},${PACKAGE}-${BRANCH_NAME}))
	$(call zip_it,.config.local.yml,${PKG_LOCAL}-local.zip)

# target: zip-preprod                                          - Bundle a pre-production zip
.PHONY: zip-preprod
zip-preprod: ${BUILD_DEPENDENCIES}
	$(eval PKG_PREPROD := $(if $(filter main,$(BRANCH_NAME)),${PACKAGE},${PACKAGE}-${BRANCH_NAME}))
	$(call zip_it,.config.preprod.yml,${PKG_PREPROD}_preprod.zip)

# target: zip-prod                                             - Bundle a production zip
.PHONY: zip-prod
zip-prod: ${BUILD_DEPENDENCIES}
	$(eval PKG_PROD := $(if $(filter main,$(BRANCH_NAME)),${PACKAGE},${PACKAGE}-${BRANCH_NAME}))
	$(call zip_it,.config.prod.yml,${PKG_PROD}.zip)

dist:
	@mkdir -p ${WORKDIR}/dist 

${BUNDLE_JS}: ${WORKDIR}/_dev/node_modules
	pnpm --filter ${WORKDIR}/_dev build

# target: build-front                                          - Build the PS Accounts front-end
.PHONY: _dev/node_modules ${BUNDLE_JS}
build-front: _dev/node_modules ${BUNDLE_JS}

${WORKDIR}/_dev/node_modules/.modules.yaml:
	pnpm --filter ${WORKDIR}/_dev install

${WORKDIR}/composer.phar:
	@php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');";
	@php composer-setup.php;
	@php -r "unlink('composer-setup.php');";

${WORKDIR}/vendor: ${WORKDIR}/composer.phar
	${WORKDIR}/composer.phar install ${COMPOSER_OPTIONS}

${WORKDIR}/tests/vendor: ${WORKDIR}/composer.phar
	${WORKDIR}/composer.phar install --working-dir tests -o

${WORKDIR}/prestashop:
	@mkdir -p ${WORKDIR}/prestashop

${WORKDIR}/${WORKDIR}/prestashop/prestashop-${PS_VERSION}: ${WORKDIR}/prestashop ${WORKDIR}/composer.phar
	@if [ ! -d "${WORKDIR}/prestashop/prestashop-${PS_VERSION}" ]; then \
		git clone --depth 1 --branch ${PS_VERSION} https://github.com/PrestaShop/PrestaShop.git ${WORKDIR}/prestashop/prestashop-${PS_VERSION} > /dev/null; \
		if [ "${PS_VERSION}" != "1.6.1.24" ]; then \
			${WORKDIR}/composer.phar -d ${WORKDIR}/${WORKDIR}/prestashop/prestashop-${PS_VERSION} install; \
    fi \
	fi;

# target: test                                                 - Static and unit testing
.PHONY: test
test: composer-validate lint php-lint phpstan phpunit

# target: docker-test                                          - Static and unit testing in docker
.PHONY: docker-test
docker-test: docker-lint docker-phpstan docker-phpunit

# target: composer-validate (or docker-composer-validate)      - Validates composer.json and composer.lock
.PHONY: composer-validate
composer-validate: vendor
	@./composer.phar validate --no-check-publish
docker-composer-validate:
	@$(call in_docker,make,composer-validate)

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
php-cs-fixer: ${TOOLS}
	@php-cs-fixer fix --dry-run --diff --config=${WORKDIR}/tests/php-cs-fixer.config.php
docker-php-cs-fixer: ${TOOLS}
	@$(call in_docker,make,lint)

# target: php-cs-fixer-fix (or docker-php-cs-fixer-fix)        - Lint the code and fix it
.PHONY: php-cs-fixer-fix docker-php-cs-fixer-fix
php-cs-fixer-fix: ${TOOLS}
	@php-cs-fixer fix --config=${WORKDIR}/tests/php-cs-fixer.config.php
docker-php-cs-fixer-fix: ${TOOLS}
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
phpunit: ${TOOLS} ${WORKDIR}/prestashop/prestashop-${PS_VERSION}
	cd ${WORKDIR}/tests &&  phpunit --configuration=phpunit.xml;
docker-phpunit: ${TOOLS}
	@$(call in_docker,make,phpunit)

# target: phpunit-cov (or docker-phpunit-cov)                  - Run phpunit with coverage and allure
.PHONY: phpunit-cov docker-phpunit-cov
phpunit-cov: ${TOOLS}
	cd ${WORKDIR}/tests && phpunit --coverage-html ./coverage-reports/coverage-html --configuration=phpunit-cov.xml;
docker-phpunit-cov: ${TOOLS}
	@$(call in_docker,make,phpunit-cov)

# target: phpstan (or docker-phpstan)                          - Run phpstan
.PHONY: phpstan docker-phpstan
phpstan: ${TOOLS} ${WORKDIR}/prestashop/prestashop-${PS_VERSION}
	cd ${WORKDIR}/tests && phpstan analyse --memory-limit=-1 --configuration=./phpstan/phpstan.neon;
docker-phpstan:
	$(call in_docker,make,phpstan)

${WORKDIR}/php-scoper.phar:
	curl -s -f -L -O "https://github.com/humbug/php-scoper/releases/download/${PHP_SCOPER_VERSION}/php-scoper.phar"
	chmod +x ${WORKDIR}/php-scoper.phar

${WORKDIR}/vendor/.scoped: ${WORKDIR}/php-scoper.phar ${WORKDIR}/scoper.inc.php vendor
	${WORKDIR}/php-scoper.phar add-prefix --output-dir ${PHP_SCOPER_OUTPUT_DIR} --force --quiet
	#for d in ${VENDOR_DIRS}; do rm -rf ${WORKDIR}/vendor/$$d && mv ${WORKDIR}/${SCOPED_DIR}/$$d ${WORKDIR}/vendor/; done;
	$(foreach DIR,$(PHP_SCOPER_VENDOR_DIRS), rm -rf "${WORKDIR}/vendor/${DIR}" && mv "${WORKDIR}/${PHP_SCOPER_OUTPUT_DIR}/${DIR}" "${WORKDIR}/vendor/${DIR}";)
	rmdir "${WORKDIR}/${PHP_SCOPER_OUTPUT_DIR}"
	make dump-autoload
	make fix-autoload
	touch ${WORKDIR}/vendor/.scoped

# target: dump-autoload                                        - Call the autoload dump from composer
.PHONY: dump-autoload
dump-autoload: ${WORKDIR}/composer.phar ${WORKDIR}/vendor
	${WORKDIR}/composer.phar dump-autoload --classmap-authoritative

# target: fix-autoload                                         - Call a custom script to fix the autoload for php-scoper
.PHONY: fix-autoload
fix-autoload:
	php ${WORKDIR}/tests/fix-autoload.php

# target: php-scoper                                           - Scope the composer dependencies
.PHONY: php-scoper
php-scoper: ${WORKDIR}/vendor ${WORKDIR}/vendor/.scoped

# target: autoindex                                            - Automatically add index.php to each folder (fix for misconfigured servers)
autoindex: ${TOOLS}
	autoindex prestashop:add:index "${WORKDIR}"

# target: header-stamp                                         - Add header stamp to files
header-stamp: ${TOOLS}
	header-stamp --target="${WORKDIR}" --license="assets/afl.txt" --exclude=".github,node_modules,vendor,tests,_dev"

# target: version                                              - Update the version in various files
version:
	echo "Setting up version: ${SEM_VERSION}..."
	sed -i.bak -e "s/\(VERSION = \).*/\1\'${SEM_VERSION}\';/" ${WORKDIR}/${MODULE_NAME}.php
	sed -i.bak -e "s/\($this->version = \).*/\1\'${SEM_VERSION}\';/" ${WORKDIR}/${MODULE_NAME}.php
	sed -i.bak -e "s|\(<version><!\[CDATA\[\)[0-9a-z.-]\{1,\}]]></version>|\1${SEM_VERSION}]]></version>|" ${WORKDIR}/config.xml
	if [ -f "${WORKDIR}/_dev/package.json" ]; then \
		sed -i.bak -e "s/\(\"version\"\: \).*/\1\"${SEM_VERSION}\",/" "${WORKDIR}/_dev/package.json"; \
		rm -f "${WORKDIR}/_dev/package.json.bak"; \
	fi
	rm -f ${WORKDIR}/${MODULE_NAME}.php.bak ${1}/config.xml.bak

define zip_it
	$(eval TMP_DIR := $(shell mktemp -d))
	mkdir -p ${TMP_DIR}/${MODULE_NAME};
	cp -r $(shell cat .zip-contents) ${TMP_DIR}/${MODULE_NAME};
	WORKDIR=${TMP_DIR}/${MODULE_NAME} make autoindex
	cp $1 ${TMP_DIR}/${MODULE_NAME}/config/config.yml
	WORKDIR=${TMP_DIR}/${MODULE_NAME} make version
	cd ${TMP_DIR} && zip -9 -r $2 ${WORKDIR}/${MODULE_NAME};
	mv ${TMP_DIR}/$2 ${WORKDIR}/dist;
	rm -rf ${TMP_DIR};
endef

define in_docker
	docker run \
	--rm \
	--user ${UID}:${GID} \
	--env _PS_ROOT_DIR_=/var/www/html \
	--workdir /var/www/html/modules/${MODULE_NAME} \
	--volume $(shell cd ${WORKDIR} && pwd):/var/www/html/modules/${MODULE_NAME}:rw \
	--entrypoint $1 ${TESTING_IMAGE} $2
endef
