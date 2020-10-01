DKC=docker-compose -f docker-compose.yml -f docker-compose.override.yml

.PHONY: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

init: ## Init project
	cp -n .env.dist .env || true
	cp -n docker-compose.override.yml.dist docker-compose.override.yml || true
	docker network create services_accounts-net > /dev/null 2>&1 || true

start: ## Start app, force rebuild all containers
	rm -f install.lock || true
	$(MAKE) init
	$(DKC) up -d --build --remove-orphans

build_node_vue_lib: ## Force reload node dependencies
	docker exec -it ps_acc_web bash -c "yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/ build-lib --fix"
	docker exec -it ps_acc_web bash -c "yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/ build-lib --fix"

build_node_dep_checkout: ## Force reload node dependencies
	docker exec -it ps_acc_web bash -c "yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/ build-lib --fix"
	docker exec -it ps_acc_web bash -c "yarn --cwd /var/www/html/modules/ps_checkout/_dev/ build --fix || true"
	docker exec -it ps_acc_web bash -c "yarn --cwd /var/www/html/modules/ps_checkout/_dev/ build --fix"

build_node_dep_metrics: ## Force reload node dependencies
	docker exec -it ps_acc_web bash -c "yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/ build-lib --fix"
	docker exec -it ps_acc_web bash -c "yarn --cwd /var/www/html/modules/ps_metrics/_dev/ build --fix || true"
	docker exec -it ps_acc_web bash -c "yarn --cwd /var/www/html/modules/ps_metrics/_dev/ build --fix"

build_node_dep_accounts: ## Force reload node dependencies
	docker exec -it ps_acc_web bash -c "yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/ build-lib --fix"
	docker exec -it ps_acc_web bash -c "yarn --cwd /var/www/html/modules/ps_accounts/_dev/ build --fix || true"
	docker exec -it ps_acc_web bash -c "yarn --cwd /var/www/html/modules/ps_accounts/_dev/ build --fix"

restart: ## Force restart all containers
	$(MAKE) down
	$(MAKE) start

down: ## Remove all ps_accounts containers
	docker rm -f ps_acc_db || true
	docker rm -f ps_acc_web || true
	docker network rm services_accounts-net || true

%:
	@:
