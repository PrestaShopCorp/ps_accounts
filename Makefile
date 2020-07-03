DKC=docker-compose -f docker-compose.yml -f docker-compose.override.yml

.PHONY: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

init: ## Init project
	cp -n .env.dist .env || true
	cp -n docker-compose.override.yml.dist docker-compose.override.yml || true
	docker network create services_accounts-net || true

start: ## Start app, force rebuild all containers
	rm -f install.lock || true
	$(MAKE) init
	$(DKC) up -d

build_node_dep: ## Force reload node dependencies
	docker exec -it ps_acc_web bash -c "yarn --cwd /tmp/libs/js/prestashop_accounts_vue_components/ build-lib"
	docker exec -it ps_acc_web bash -c "yarn --cwd /var/www/html/modules/ps_checkout/_dev/ build || true"
	docker exec -it ps_acc_web bash -c "yarn --cwd /var/www/html/modules/ps_checkout/_dev/ build"

restart: ## Force restart all containers
	$(MAKE) down
	$(MAKE) start

down: ## Remove all ps_accounts containers
	docker rm -f ps_acc_db || true
	docker rm -f ps_acc_web || true
	docker network rm services_accounts-net || true

%:
	@:
