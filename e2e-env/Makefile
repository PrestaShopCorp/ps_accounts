PROFILE ?= flashlight

help: ## Show this help message
	@echo "Usage: make [target]"
	@echo ""
	@echo "Available targets:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  %-20s %s\n", $$1, $$2}'
.PHONY: help

docker-build: ## Build docker env 
	docker compose --profile all down -v 
	PS_DOMAIN=${PS_DOMAIN} ACCOUNT_TAG=${ACCOUNT_TAG} TUNNEL_SECRET=${TUNNEL_SECRET} TUNNEL_ID=${TUNNEL_ID} ./myTun/config/generateConfigFiles.sh $(PROFILE)
	PS_VERSION=${PS_VERSION} SECONDE_PS_VERSION=${SECONDE_PS_VERSION} PS_DOMAIN=${PS_DOMAIN} PS_ACCOUNTS_VERSION=${PS_ACCOUNTS_VERSION} docker compose --profile $(PROFILE) up --build --force-recreate -d
.PHONY: docker-build
