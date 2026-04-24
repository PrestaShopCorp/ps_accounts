PROFILE ?= flashlight
PS_ACCOUNTS_VERSION ?= ${PS_ACCOUNTS_VERSION}

help: ## Show this help message
	@echo "Usage: make [target]"
	@echo ""
	@echo "Available targets:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  %-20s %s\n", $$1, $$2}'
.PHONY: help

docker-build: ## Build docker env 
	docker compose --profile all down -v
	docker network inspect ps_accounts_net >/dev/null 2>&1 || docker network create ps_accounts_net
	PS_DOMAIN=${PS_DOMAIN} ACCOUNT_TAG=${ACCOUNT_TAG} TUNNEL_SECRET=${TUNNEL_SECRET} TUNNEL_ID=${TUNNEL_ID} ./myTun/config/generateConfigFiles.sh $(PROFILE)
	@if [ "$(PROFILE)" = "multistore" ]; then \
		SHOP1_PHYSICAL_URI=shop1; \
		SHOP2_PHYSICAL_URI=shop2; \
		SHOP1_TRAEFIK_RULE="Host(\`$(PS_DOMAIN)\`) && PathPrefix(\`/shop1\`)"; \
		SHOP2_TRAEFIK_RULE="Host(\`$(PS_DOMAIN)\`) && PathPrefix(\`/shop2\`)"; \
	else \
		SHOP1_PHYSICAL_URI=; \
		SHOP2_PHYSICAL_URI=; \
		SHOP1_TRAEFIK_RULE="Host(\`$(PS_DOMAIN)\`)"; \
		SHOP2_TRAEFIK_RULE="Host(\`$(PS_DOMAIN)\`) && PathPrefix(\`/shop2\`)"; \
	fi; \
	PS_VERSION=${PS_VERSION} SECONDE_PS_VERSION=${SECONDE_PS_VERSION} PS_DOMAIN=${PS_DOMAIN} PS_ACCOUNTS_VERSION=${PS_ACCOUNTS_VERSION} SHOP1_PHYSICAL_URI="$$SHOP1_PHYSICAL_URI" SHOP2_PHYSICAL_URI="$$SHOP2_PHYSICAL_URI" SHOP1_TRAEFIK_RULE="$$SHOP1_TRAEFIK_RULE" SHOP2_TRAEFIK_RULE="$$SHOP2_TRAEFIK_RULE" docker compose --profile $(PROFILE) up --build --force-recreate -d
.PHONY: docker-build
