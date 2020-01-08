DKC=docker-compose -f docker-compose.yml -f docker-compose.override.yml


.PHONY: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

init: ## Init project
	cp -n .env.dist .env || true
	cp -n docker-compose.override.yml.dist docker-compose.override.yml || true

yarn_start: ## Start VueJs
	$(MAKE) yarn_install
	$(MAKE) yarn_watch

start: ## Start app
	$(DKC) up -d --build
	$(MAKE) yarn_start

run: ## Run app
	$(DKC) up -d
	$(MAKE) yarn_start

yarn_install: ## Install depedencies nodejs
	$(DKC) run --rm ps_account_node sh -c "yarn install"

yarn_build: ## Build vuejs file
	$(DKC) run --rm ps_account_node sh -c "yarn build"

yarn_watch: ## Watch VueJS files and compile when saved
	$(DKC) run --rm ps_account_node sh -c "yarn watch"

%:
	@:

