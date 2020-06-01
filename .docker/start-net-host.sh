
#!/bin/bash

CURRENTPATHDIR=$1
source $CURRENTPATHDIR/.env

# Run only on Linux
# /!\ The ports `80` and `3306` should not be used by your host.
docker rm -f ps_acc_db || true
docker rm -f ps_acc_web || true
docker run -ti --name ps_acc_db --env-file=$CURRENTPATHDIR/.env --net=host -d mysql:5.7
docker run -ti --name ps_acc_web --net=host --env-file=$CURRENTPATHDIR/.env \
	-v $CURRENTPATHDIR:/var/www/html/modules/ps_accounts \
	-v $PS_CHECKOUT_PATH:/var/www/html/modules/ps_checkout \
	-v $PRESTASHOP_ACCOUNTS_AUTH_PATH:/tmp/libs/php/prestashop_accounts_auth \
	-v $PRESTASHOP_ACCOUNTS_VUE_COMPONENTS_PATH:/tmp/libs/js/prestashop_accounts_vue_components \
	-v $CURRENTPATHDIR/.docker/install_module.sh:/tmp/init-scripts/install_module.sh \
	-d prestashop/prestashop
