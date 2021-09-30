.PHONY: clean help build bundle zip version bundle-prod bundle-inte build-front build-back
PHP = $(shell which php 2> /dev/null)
DOCKER = $(shell docker ps 2> /dev/null)
NPM = $(shell which npm 2> /dev/null)
YARN = $(shell which yarn 2> /dev/null)
VERSION ?= $(shell git describe --tags)

SEM_VERSION ?= $(shell git describe --tags | sed 's/^v//')
MODULE ?= $(shell basename ${PWD})
PACKAGE ?= "${MODULE}-${VERSION}"
PHPSTAN_VERSION ?= 0.12
PHPUNIT_VERSION ?= latest
PS_VERSION ?= 1.7.7.1
NEON_FILE ?= phpstan-PS-1.7.neon

# target: default                                - Calling build by default
default: build

# target: help                                   - Get help on this file
help:
	@egrep "^#" Makefile

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
	echo "...$(VERSION)..."
	sed -i "" -e "s/\(VERSION = \).*/\1\'${SEM_VERSION}\';/" ps_accounts.php
	sed -i "" -e "s/\($this->version = \).*/\1\'${SEM_VERSION}\';/" ps_accounts.php
	sed -i "" -e "s|\(<version><!\[CDATA\[\)[0-9a-z.-]\{1,\}]]></version>|\1${SEM_VERSION}]]></version>|" config.xml

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
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
	php composer-setup.php
	php -r "unlink('composer-setup.php');"

# target: tests                                  - Launch the tests/lints suite front and back
tests: test-back test-front lint-back

# target: test-back                              - Launch the tests back
test-back: lint-back phpstan phpunit

# target: lint-back                              - Launch the back linting
lint-back:
	vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --diff-format udiff

# target: phpstan                                - Start phpstan
phpstan:
ifndef DOCKER
    $(error "DOCKER is unavailable on your system")
endif
	docker pull phpstan/phpstan:${PHPSTAN_VERSION}
	docker pull prestashop/prestashop:${PS_VERSION}
	docker run --rm -d -v ps-volume:/var/www/html --entrypoint /bin/sleep --name test-phpstan prestashop/prestashop:${PS_VERSION} 2s
	docker run --rm --volumes-from test-phpstan \
	  -v ${PWD}:/web/module \
	  -e _PS_ROOT_DIR_=/var/www/html \
	  --workdir=/web/module \
	  phpstan/phpstan:${PHPSTAN_VERSION} analyse \
	  --configuration=/web/module/tests/phpstan/${NEON_FILE}

# target: phpunit                                - Start phpunit
phpunit: phpunit-cleanup
ifndef DOCKER
    $(error "DOCKER is unavailable on your system")
endif
	docker run --rm -d -e PS_DOMAIN=localhost -e PS_ENABLE_SSL=0 -e PS_DEV_MODE=1 --name test-phpunit prestashop/docker-internal-images:1.7
	-docker container exec -u www-data test-phpunit sh -c "sleep 1 && php -d memory_limit=-1 ./bin/console prestashop:module uninstall ps_accounts"
	docker cp . test-phpunit:/var/www/html/modules/ps_accounts
	docker cp ./config/config.yml.dist test-phpunit:/var/www/html/modules/ps_accounts/config/config.yml
	docker container exec -u www-data test-phpunit sh -c "sleep 1 && php -d memory_limit=-1 ./bin/console prestashop:module install ps_accounts"
	@docker container exec -u www-data test-phpunit sh -c "echo \"Testing module v\`cat /var/www/html/modules/ps_accounts/config.xml | grep '<version>' | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/'\`\n\""
	docker container exec -u www-data --workdir /var/www/html/modules/ps_accounts test-phpunit ./vendor/bin/phpunit
	@echo phpunit passed

phpunit-cleanup:
	-docker container rm -f test-phpunit

phpunit-debug:
	docker container exec -u www-data --workdir /var/www/html/modules/ps_accounts test-phpunit ./vendor/bin/phpunit
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

