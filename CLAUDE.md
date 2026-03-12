# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`ps_accounts` is a PrestaShop module that acts as the authentication and account management layer between PrestaShop shops and PrestaShop Cloud services. It manages shop identification/verification, OAuth2 token provisioning, and shop synchronization. It must support PrestaShop 1.6 through 9.x with PHP 5.6–8.3.

## Commands

### PHP Dependencies
```bash
# Install composer (if not present)
./scripts/composer-install.sh

# Install main vendor deps (production, no-dev)
php ./composer.phar install --prefer-dist -o --no-dev

# Install test deps (uses composer56.json by default)
env COMPOSER=composer56.json php ./composer.phar install --working-dir=./tests/
```

### Frontend (TypeScript/Vite)
```bash
# Build frontend assets (outputs to views/js/ and views/css/)
make build-front
# Equivalent to:
pnpm --filter ./_dev install --frozen-lockfile --ignore-scripts
pnpm --filter ./_dev build
```

### Bundling
```bash
make bundle          # Full bundle: php-scoper + config + front → ps_accounts.zip
make bundle-prod     # Production bundle
make bundle-preprod  # Pre-production bundle
```

### Testing (requires Docker platform to be running)
```bash
# Start a test platform (Docker + PrestaShop + module install)
make platform-8.1.5-7.4   # PS 8.1.5 on PHP 7.4 (most common)
make platform-8.2.0-8.1   # PS 8.2.0 on PHP 8.1

# Run tests (inside running platform container)
make phpunit               # All tests (unit + feature)
make phpunit-run-unit      # Unit tests only
make phpunit-run-feature   # Feature tests only

# Run a single test class inside the container:
docker exec -w /var/www/html/modules/ps_accounts/tests phpunit \
  ./vendor/bin/phpunit --filter TestClassName

# Combined: spin up platform and run tests
make phpunit-8.1.5-7.4
```

### Code Quality
```bash
make php-cs-fixer-test    # Dry-run style check
make php-cs-fixer         # Fix style issues
make header-stamp-test    # Validate AFL license headers
make phpstan              # Static analysis (runs inside Docker)
```

## Architecture

### PHP Scope & Vendor Isolation

All third-party vendor dependencies are scoped under `PrestaShop\Module\PsAccounts\Vendor\*` using [php-scoper](https://github.com/humbug/php-scoper). This prevents namespace collisions with other PrestaShop modules. Never reference unscoped vendor namespaces in module code.

### Dependency Injection

Uses a custom lightweight DI container (`prestashopcorp/lightweight-container`) instead of Symfony's container, for PHP 5.6 compatibility and to avoid the Symfony ecosystem entirely. Services are declared in `config/services.yml`. The container is bootstrapped in `src/ServiceContainer/` and exposed via the module's `getService()` method.

### HTTP Client

Uses raw curl (no Guzzle, no PSR-18) because curl is the only stable HTTP interface across PS 1.6–9. HTTP client lives in `src/Http/`.

### Key Source Areas

| Path | Purpose |
|------|---------|
| `ps_accounts.php` | Module entry point, hook registration, service container bootstrap |
| `src/Service/PsAccountsService.php` | Main public API consumed by other modules |
| `src/Service/OAuth2/` | OAuth2 token flow, refresh logic, token storage |
| `src/Account/` | CQRS commands/queries for account state, sessions |
| `src/Account/Session/` | Firebase shop/owner session management |
| `src/Controller/Admin/` | Admin panel controllers |
| `src/Controller/Front/` | Front-office/API controllers |
| `src/Api/V2/` | REST API v2 endpoints |
| `src/Repository/` | Database access layer (PrestaShop ObjectModel) |
| `src/Hook/` | PrestaShop hook handlers |
| `src/Installer/` | Module install/uninstall/upgrade logic |
| `src/ServiceContainer/` | DI container setup and service providers |
| `_dev/apps/` | TypeScript/Vue frontend (compiled to `views/`) |
| `config/` | YAML service/route definitions |
| `tests/src/Unit/` | Unit tests |
| `tests/src/Feature/` | Feature/integration tests |

### Data Flow

```
Other modules → PsAccountsService
                     ↓
            OAuth2Service (token management)
                     ↓
            Repository (DB: shop UUID, tokens, refresh tokens)
                     ↓
            External: accounts-api / auth-hydra (OAuth2 server)
```

### Frontend

Two Vite apps under `_dev/apps/`:
- **Login app** – OAuth2 login/signup popup flow
- **Notifications app** – Admin panel notifications

Built assets are output to `views/js/app.{version}.js` and `views/css/app.{version}.css`.

## Important Constraints

- **PHP 5.6 compatibility required** for all `src/` code (some CI targets test on PHP 5.6). Avoid PHP 7+ syntax (typed properties, union types, named arguments, etc.) unless gated.
- **No Symfony dependencies** in the module core (lightweight-container only).
- **No Guzzle** – use the internal curl-based HTTP client.
- All new PHP files must carry the AFL-3.0 license header (enforced by `header-stamp`).
- Test vendor is installed separately from module vendor (`tests/composer*.json`).
- `config.php` is generated from `config.dist.php` and is gitignored; copy it with `cp config.dist.php config.php` before running locally.
