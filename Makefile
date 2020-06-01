include .env
export $(shell sed 's/=.*//' .env)

#all                  	- Init VueJs and Start docker containers Module
all: help

#init                  - Init project
init:
	cp -n .env.dist .env || true

#start                 - Start app, force rebuild all containers
start:
	rm -f install.lock || true
	$(MAKE) down
	docker run -ti --name ps_acc_db --env-file=.env --net=host -d mysql:5.7
	docker run -ti --name ps_acc_web --net=host --env-file=.env \
	-v `pwd`:/var/www/html/modules/ps_accounts \
	-v $$PS_CHECKOUT_PATH:/var/www/html/modules/ps_checkout \
	-v $$PRESTASHOP_ACCOUNTS_AUTH_PATH:/tmp/libs/php/prestashop_accounts_auth \
	-v $$PRESTASHOP_ACCOUNTS_VUE_COMPONENTS_PATH:/tmp/libs/js/prestashop_accounts_vue_components \
	-v `pwd`/.docker/install_module.sh:/tmp/init-scripts/install_module.sh \
	-d prestashop/prestashop

#down                  - Remove all ps_accounts containers
down: ## Remove all ps_accounts containers
	docker rm -f ps_acc_db || true
	docker rm -f ps_acc_web || true

#help                  - Help
help:
	@egrep "^#" Makefile
