help: ## Show this help message
	@echo "Usage: make [target]"
	@echo ""
	@echo "Available targets:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  %-20s %s\n", $$1, $$2}'
.PHONY: help

docker-build: ## Build 8.0.x docker env
	PS_VERSION=${PS_VERSION} PS_DOMAIN=${PS_DOMAIN} PS_ACCOUNTS_VERSION=${PS_ACCOUNTS_VERSION} docker compose up --build --force-recreate -d
.PHONY: docker-build