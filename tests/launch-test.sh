#!/bin/bash
# enable/disable rm container
# copy test config
docker run --rm -d -e PS_DOMAIN=localhost -e PS_ENABLE_SSL=0 -e PS_DEV_MODE=1 --name test-phpunit prestashop/docker-internal-images:1.7
docker container exec test-phpunit sh -c "rm -rf /var/www/html/modules/ps_accounts"
docker cp . test-phpunit:/var/www/html/modules/ps_accounts
docker container exec -u www-data test-phpunit sh -c "sleep 1 && ./bin/console prestashop:module install ps_accounts"
docker container exec -u www-data --workdir /var/www/html/modules/ps_accounts test-phpunit ./vendor/bin/phpunit ./tests/Feature/Api
docker container rm -f test-phpunit
echo phpunit finished
