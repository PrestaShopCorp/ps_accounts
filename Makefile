include .env

DKC=docker-compose -f docker-compose.yml -f docker-compose.override.yml

.PHONY: help

# target: init                       	- Start app, force rebuild all containers
init:
	cp -n .env.dist .env || true
	ln -s $$(pwd)/.env $$(pwd)/_dev/.env || true
	cp -n docker-compose.override.yml.dist docker-compose.override.yml || true

# target: start                     	- Start app, force rebuild all containers
start:
	rm -f install.lock
	$(DKC) up -d --build --force-recreate
	$(MAKE) yarn_watch

# target: run                       	- Run app
run:
	$(DKC) up -d
	$(MAKE) yarn_watch

# target: yarn_install                	- Install depedencies nodejs
yarn_install:
	$(DKC) run --rm ps_account_web sh -c "yarn --cwd _dev/ install"

# target: yarn_build                	- Build vuejs file
yarn_build:
	$(DKC) run --rm ps_account_web sh -c "yarn --cwd _dev/ build"

# target: yarn_watch                	- Watch VueJS files and compile when saved
yarn_watch:
	$(DKC) run --rm ps_account_web sh -c "yarn --cwd _dev/ start:dev"

help:
	@egrep "^#" Makefile
