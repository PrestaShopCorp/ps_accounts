.PHONY: clean help build bundle zip version bundle-prod bundle-inte build-front build-back
PHP = $(shell which php 2> /dev/null)
DOCKER = $(shell docker ps 2> /dev/null)
NPM = $(shell which npm 2> /dev/null)
YARN = $(shell which yarn 2> /dev/null)

VERSION ?= 5.2.0#$(shell git describe --tags | sed 's/^v//')
MODULE ?= $(shell basename ${PWD})
PACKAGE ?= "${MODULE}-${VERSION}"
PHPSTAN_VERSION ?= 0.12
PHPUNIT_VERSION ?= latest
PS_VERSION ?= latest #1.7.7.1
NEON_FILE ?= phpstan-PS-1.7.neon
DOCKER_INTERNAL ?= nightly # 1.7|nightly

# target: default                                - Calling build by default
default: build

# target: help                                   - Get help on this file
help:
	@egrep "^# target" Makefile

# target: build                                  - Clean up the repository
clean:
	git -c core.excludesfile=/dev/null clean -X -d -f

# target: bundle                                 - Bundle local sources into a ZIP file
bundle: bundle-inte bundle-prod

# target: zip                                    - Alias of target: bundle
zip: bundle

# target: dist                                   - A directory to save zip bundles
dist:
	mkdir -p ./dist

# target: version                                - Replace version in files
version:
	@echo "...$(VERSION)..."
	sed -i -e "s/\(VERSION = \).*/\1\'${VERSION}\';/" ps_accounts.php
	sed -i -e "s/\($this->version = \).*/\1\'${VERSION}\';/" ps_accounts.php
	sed -i -e 's/\(<version><!\[CDATA\[\)[0-9a-z\.\-]\{1,\}.*\]\]><\/version>/\1'${VERSION}']]><\/version>/' config.xml
	sed -i -e "s/\(\"version\"\: \).*/\1\"${VERSION}\",/" ./_dev/package.json

# target: bundle-prod                            - Bundle a production zip
bundle-prod: dist ./vendor ./views/index.php
	cd .. && zip -r ${PACKAGE}_prod.zip ${MODULE} -x '*.git*' \
	  ${MODULE}/_dev/\* \
	  ${MODULE}/dist/\* \
	  ${MODULE}/composer.phar \
	  ${MODULE}/Makefile
	mv ../${PACKAGE}_prod.zip ./dist

# target: bundle-inte                            - Bundle an integration zip
bundle-inte: dist ./vendor ./views/index.php
	cp config/config.yml config/config.yml.local
	cp config/config.preprod.yml config/config.yml
	cd .. && zip -r ${PACKAGE}_inte.zip ${MODULE} -x '*.git*' \
	  ${MODULE}/_dev/\* \
	  ${MODULE}/dist/\* \
	  ${MODULE}/composer.phar \
	  ${MODULE}/Makefile
	mv ../${PACKAGE}_inte.zip ./dist
	mv config/config.yml.local config/config.yml

# target: build                                  - Setup PHP & Node.js locally
build: build-front build-back

# target: build-front                            - Build front for prod locally
build-front:
ifndef YARN
    $(error "YARN is unavailable on your system, try `npm i -g yarn`")
endif
	yarn --cwd ./_dev --frozen-lockfile
	yarn --cwd ./_dev build

# target: build-back                             - Build production dependencies
build-back: composer.phar
	./composer.phar install --no-dev

composer.phar:
ifndef PHP
    $(error "PHP is unavailable on your system")
endif
	./scripts/composer-install.sh

# target: tests                                  - Launch the tests/lints suite front and back
tests: test-back test-front lint-back

# target: test-back                              - Launch the tests back
test-back: lint-back phpstan phpunit

# target: lint-back                              - Launch the back linting
lint-back:
	vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --diff-format udiff

check-docker:
ifndef DOCKER
    $(error "DOCKER is unavailable on your system")
endif

# target: phpstan                                - Start phpstan
phpstan: check-docker
	docker pull phpstan/phpstan:${PHPSTAN_VERSION}
	docker pull prestashop/prestashop:${PS_VERSION}
	docker run --rm -d -v ps-volume:/var/www/html --entrypoint /bin/sleep --name test-phpstan prestashop/prestashop:${PS_VERSION} 2s
	docker run --rm --volumes-from test-phpstan \
	  -v ${PWD}:/web/module \
	  -e _PS_ROOT_DIR_=/var/www/html \
	  --workdir=/web/module \
	  phpstan/phpstan:${PHPSTAN_VERSION} analyse \
	  --configuration=/web/module/tests/phpstan/${NEON_FILE}
	docker volume rm ps-volume

# target: phpunit                                - Start phpunit
# FIXME: create two command to run test (feature with apache2 started et unit with just mysql
#PHPUNIT_CMD="./vendor/bin/phpunit --colors=always || bash"
PHPUNIT_CMD="./vendor/bin/phpunit --colors=always"
phpunit: check-docker
	-docker container rm -f phpunit
	@docker run --rm -ti \
		--name phpunit \
		-e PS_DOMAIN=localhost \
		-e PS_ENABLE_SSL=0 \
		-e PS_DEV_MODE=1 \
		-v ${PWD}:/var/www/html/modules/ps_accounts \
		-w /var/www/html/modules/ps_accounts \
		prestashop/docker-internal-images:${DOCKER_INTERNAL} \
		sh -c " \
			service mysql start && \
			service apache2 start && \
			../../bin/console prestashop:module install ps_accounts && \
			echo \"Testing module v\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\" && \
			chown -R www-data:www-data ../../var/logs && \
			${PHPUNIT_CMD} \
		      "
	@echo phpunit passed

vendor/phpunit/phpunit:
	./composer.phar install

# target: test-front                             - Launch the tests front (does not work linter is not configured)
test-front:
	npm --prefix=./_dev run lint

# target: fix-lint                               - Launch php cs fixer and npm run lint
fix-lint: vendor/bin/php-cs-fixer
	vendor/bin/php-cs-fixer fix --using-cache=no
	npm --prefix=./_dev run lint --fix

vendor/bin/php-cs-fixer:
	./composer.phar install


