.PHONY: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

init: ## Init project
	cp -n .env.dist .env || true

start: ## Start app, force rebuild all containers
	$(MAKE) init
	docker-compose up -d

restart: ## Force restart all containers
	$(MAKE) down
	$(MAKE) start

down: ## Remove all ps_accounts containers
	docker rm -f ps_acc_db || true
	docker rm -f ps_acc_web || true

%:
	@:
