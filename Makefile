include .env

DKC=docker-compose -f docker-compose.yml -f docker-compose.override.yml

.PHONY: help

# target: init                       	- Start app, force rebuild all containers
init:
	cp -n .env.dist .env || true
	ln -s $$(pwd)/.env $$(pwd)/_dev/.env || true
	cp -n docker-compose.override.yml.dist docker-compose.override.yml || true

# target: yarn_start                	- Start VueJs
yarn_start:
	$(MAKE) yarn_install
	$(MAKE) yarn_watch

# target: start                     	- Start app, force rebuild all containers
start:
	$(DKC) up -d --build --force-recreate
	$(MAKE) yarn_start

# target: run                       	- Run app
run:
	$(DKC) up -d
	$(MAKE) yarn_start

# target: yarn_install                	- Install depedencies nodejs
yarn_install:
	$(DKC) run --rm ps_account_node sh -c "yarn install"

# target: yarn_build                	- Build vuejs file
yarn_build:
	$(DKC) run --rm ps_account_node sh -c "yarn build"

# target: yarn_watch                	- Watch VueJS files and compile when saved
yarn_watch:
	$(DKC) run --rm ps_account_node sh -c "yarn start:dev"
	# docker run --rm -w /app -v $(PRESTASHOP_ACCOUNTS_VUE_COMPONENTS_PATH):/app --name ps_acc_node node:lts-stretch sh -c "yarn start:dev && tail -f /dev/null"

help:
	@egrep "^#" Makefile
