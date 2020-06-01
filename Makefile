DKC=docker-compose -f docker-compose.yml -f docker-compose.override.yml

.PHONY: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

init: ## Init project
	cp -n .env.dist .env || true
	cp -n docker-compose.override.yml.dist docker-compose.override.yml || true

start: ## Start app, force rebuild all containers
	rm -f install.lock || true
	$(MAKE) init
	$(DKC) up -d

start-net-host: ## Start app with host network (Linux only)
	rm -f install.lock || true
	$(MAKE) init
	bash ./.docker/start-net-host.sh `pwd`

restart: ## Force restart all containers
	$(MAKE) down
	$(MAKE) start

down: ## Remove all ps_accounts containers
	docker rm -f ps_acc_db || true
	docker rm -f ps_acc_web || true

%:
	@:
