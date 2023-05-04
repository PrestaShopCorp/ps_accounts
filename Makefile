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

phpunit-pull:
	docker pull prestashop/docker-internal-images:${DOCKER_INTERNAL}

phpunit-start:
	@docker-compose up -d
	@echo phpunit started

phpunit-stop:
	@docker-compose down
	@echo phpunit stopped

phpunit-restart: phpunit-stop phpunit-start

phpunit-module-config:
	@docker exec -w /var/www/html/modules/ps_accounts phpunit \
		sh -c "if [ ! -f ./config/config.yml ]; then cp ./config/config.yml.dist ./config/config.yml; fi"

phpunit-module-version:
	@docker exec -w /var/www/html/modules/ps_accounts phpunit \
		sh -c "echo \"Module v\`cat config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\""

phpunit-module-install: phpunit-module-config phpunit-module-version
	@sleep 5
	@#@docker exec phpunit sh -c "docker-php-ext-enable xdebug"
	@docker exec phpunit sh -c "php -d memory_limit=-1 ./bin/console prestashop:module install ps_accounts"

phpunit-permissions:
	@docker exec phpunit sh -c "chown -R www-data:www-data ./var"

phpunit-run-unit: phpunit-permissions
	@docker exec -w /var/www/html/modules/ps_accounts phpunit sh -c "./vendor/bin/phpunit --testsuite unit"

phpunit-run-domain: phpunit-permissions
	@docker exec -w /var/www/html/modules/ps_accounts phpunit sh -c "./vendor/bin/phpunit --testsuite domain"

phpunit-run-feature: phpunit-permissions
	@docker exec -w /var/www/html/modules/ps_accounts phpunit sh -c "./vendor/bin/phpunit --testsuite feature"

# target: phpunit                                - Start phpunit
phpunit: phpunit-pull phpunit-restart phpunit-module-install phpunit-run-feature phpunit-run-domain phpunit-run-unit
	@echo phpunit passed

phpunit-dev: phpunit-pull phpunit-restart phpunit-module-install phpunit-permissions
	@echo phpunit container is ready

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

