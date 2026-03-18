# Accounts — CLAUDE.md

> This file is read by Claude Code at every session. It overrides any ad hoc configuration.

---

## 1. Squad context

**Squad:** Squad Account
**Functional domain:** Token generation, OAuth2 authentication flow, shop identification/verification, synchronization with PrestaShop Cloud
**Main stack:** PHP 5.6–8.3 / Node.js / TypeScript / Vue 3 / Vite / PrestaShop 1.6–9.x
**Architecture pattern:** CQRS in `src/Account/` · legacy controllers in `controllers/`

---

## 2. Project structure

| Path                                | Purpose                                                            |
|-------------------------------------|--------------------------------------------------------------------|
| `ps_accounts.php`                   | Module entry point, hook registration, DI bootstrap               |
| `src/Service/PsAccountsService.php` | Main public API consumed by other modules                         |
| `src/Service/OAuth2/`               | OAuth2 flow, refresh logic, token storage                         |
| `src/Account/`                      | CQRS: commands/queries for account and session state              |
| `src/Account/Session/`              | Firebase shop/owner session management                            |
| `src/Controller/Admin/`             | Back-office controllers                                           |
| `src/Controller/Front/`             | Front-office / API controllers                                    |
| `src/Api/V2/`                       | REST API v2 endpoints                                             |
| `src/Repository/`                   | DB access layer (PrestaShop ObjectModel)                          |
| `src/Hook/`                         | PrestaShop hook handlers                                          |
| `src/Installer/`                    | Module install/uninstall/upgrade logic                            |
| `src/ServiceContainer/`             | DI container setup and service providers                          |
| `src/Http/`                         | Internal curl-based HTTP client                                   |
| `sql/`                              | SQL migration scripts                                             |
| `translations/`                     | Module translations                                               |
| `views/`                            | Smarty templates, assets, compiled CSS/JS                         |
| `templates/`                        | Twig templates                                                    |
| `upgrade/`                          | Module upgrade scripts                                            |
| `controllers/`                      | Legacy controllers                                                |
| `_dev/apps/`                        | TypeScript/Vue frontend (compiled to `views/`)                    |
| `config/`                           | YAML service/route definitions (PrestaShop/Symfony routing)       |
| `tests/src/Unit/`                   | Unit tests                                                        |
| `tests/src/Feature/`                | Feature / integration tests                                       |

**Main entry point:** `ps_accounts.php`
**Critical config files:** `config.php` (generated from `config.dist.php`, gitignored)

### Data flow

```
Other modules → PsAccountsService
                      ↓
             OAuth2Service (token management)
                      ↓
             Repository (DB: shop UUID, tokens, refresh tokens)
                      ↓
             External: accounts-api / auth-hydra (OAuth2 server)
```

---

## 3. Code conventions ⚡

**Naming:**
- CQRS commands: `[Action][Entity]Command` — e.g. `CreateIdentityCommand`
- CQRS handlers: `[Action][Entity]Handler` — e.g. `CreateIdentityHandler`
- Tests: `[TestedClass]Test.php` — methods annotated with `@test`, named `itShould[Action][Context]` (no `test` prefix)

**Mandatory PHP constraints:**
- **PHP 5.6 compatible** for all `src/` code — no typed properties, union types, named arguments, or PHP 7+ syntax
- All third-party vendor dependencies are scoped under `PrestaShop\Module\PsAccounts\Vendor\*` via php-scoper — **never reference unscoped vendor namespaces**
- Every new PHP file must carry the AFL-3.0 license header (enforced by `header-stamp`)

**Patterns in use:**
- Lightweight DI container (`prestashopcorp/lightweight-container`) — services declared in `config/services.yml`
- HTTP client: raw curl only (no Guzzle, no PSR-18)
- DB access exclusively through Repositories

**Anti-patterns to avoid:**
- No direct modification of `ps_configuration` — go through Repositories
- No direct DB calls in handlers — go through Repositories
- No `use Symfony\` in the module core code
- No `use GuzzleHttp\` or PSR-18 — internal curl client only
- Never reference unscoped vendor namespaces

---

## 4. Restricted areas 🚫

- `src/Service/OAuth2/` and `src/Account/Session/` — authentication/sessions: any modification requires mandatory review
- `sql/` — SQL migrations: never generate automatically
- `upgrade/` — upgrade scripts: risk of regression on existing shops
- `src/Service/PsAccountsService.php` — public API consumed by third-party modules, BC breaks forbidden
- `config/prod/` — production configuration
- Scoped vendors (`vendor/`, `dist/`) — do not modify manually

**If Claude proposes modifying a restricted area:** ask it to explain the alternative without touching that area.

---

## 5. Tests 🧪

**Framework:** PHPUnit (compatible PHP 5.6–8.x)

**Prerequisites:** Docker must be running with an active test platform.

```bash
# Start a test platform (Docker + PrestaShop + module install)
make platform-8.1.5-7.4   # PS 8.1.5 on PHP 7.4 (most common)
make platform-8.2.0-8.1   # PS 8.2.0 on PHP 8.1

# Run all tests (unit + feature)
make phpunit

# Unit tests only
make phpunit-run-unit

# Feature / integration tests only
make phpunit-run-feature

# Combo: start platform AND run tests
make phpunit-8.1.5-7.4

# Run a specific test or class inside the container
docker exec -w /var/www/html/modules/ps_accounts/tests phpunit \
  ./vendor/bin/phpunit --filter TestClassName

# Install test dependencies
env COMPOSER=composer56.json php ./composer.phar install --working-dir=./tests/
```

**Test locations:**
- `tests/src/Unit/` — unit tests (mirrors `src/`)
- `tests/src/Feature/` — feature/integration tests

**Test naming convention:** `[TestedClass]Test.php`, methods annotated with `@test`, named `itShould[Action][Context]` (no `test` prefix)

---

## 6. Development workflow

**Branches:** `feature/[ticket-id]-description` · `fix/[ticket-id]-description` · no direct commits to `main`
**Commit format:** `feat(scope): description` · `fix(scope): description` (conventional commits)
**PR:** mandatory review before merge

```bash
# Code quality
make php-cs-fixer-test    # Style check (dry-run)
make php-cs-fixer         # Auto-fix style
make header-stamp-test    # Validate AFL license headers
make phpstan              # Static analysis (runs in Docker)

# Frontend build
make build-front
# Equivalent to:
pnpm --filter ./_dev install --frozen-lockfile --ignore-scripts
pnpm --filter ./_dev build

# Bundling
make bundle          # Full bundle: php-scoper + config + front → ps_accounts.zip
make bundle-prod     # Production bundle
make bundle-preprod  # Pre-production bundle

# PHP dependencies
./scripts/composer-install.sh               # Install composer if missing
php ./composer.phar install --prefer-dist -o --no-dev  # Production deps
```

**Before proposing a change, Claude must:**
1. Run unit tests for the modified area
2. Verify no restricted area is impacted
3. Propose the corresponding test if it does not exist

---

## 7. Business glossary

| Term                    | Definition                                                                                                                                                    |
|-------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Shop UUID / CloudShopId | Unique identifier of the PrestaShop shop on the accounts-api side                                                                                            |
| TokenV2                 | Shop OAuth2 token issued by auth-hydra: JWT access_token + opaque refresh_token. Stored in config (`PS_ACCOUNTS_ACCESS_TOKEN`) via `ConfigurationRepository` |
| OAuth2Client            | Shop credentials (clientId / clientSecret) used to obtain a TokenV2 via client credentials flow                                                              |
| Owner token             | Firebase token of the account owner administrator — **deprecated**                                                                                           |
| Shop token              | Firebase token of the shop (distinct from the owner token) — **deprecated**, replaced by `TokenV2`                                                           |
| accounts-api            | External PrestaShop Cloud API that manages merchant accounts                                                                                                 |
| auth-hydra              | PrestaShop OAuth2 server (Ory Hydra) that issues TokenV2s                                                                                                    |
| Identity                | Representation of an identified shop on the accounts side — see `src/Account/`                                                                               |
| Proof                   | Shop ownership verification mechanism (`ProofManager`)                                                                                                       |
| Session                 | Shop session abstraction — `src/Account/Session/ShopSession.php` (OAuth2, active) · `src/Account/Session/Firebase/` (deprecated)                             |
| Command                 | CQRS command (not to be confused with PrestaShop commands)                                                                                                   |
| Scoped vendor           | Vendor namespace prefixed with `PrestaShop\Module\PsAccounts\Vendor\*` via php-scoper                                                                        |

---

## 8. What Claude does well in this project ✅

- Generate CQRS handlers from an existing Command (follow the pattern in `src/Account/CommandHandler/`)
- Write PHPUnit unit tests for repositories and services
- Analyze the OAuth2 flow and explain token exchanges
- Identify BC impacts of a change on `PsAccountsService`
- Adapt code to maintain PHP 5.6 compatibility

---

## 9. What always requires human review ⚠️

- Any public interface modification of `PsAccountsService` (potential BC break for third-party modules)
- Generation or modification of SQL migrations (`sql/`)
- Changes to session management or OAuth2 authentication
- Modifications to upgrade scripts (`upgrade/`)
- Any change to the DI container configuration (`config/services.yml`)
- Updating or adding vendor dependencies (impacts on php-scoper scope)

---

*Last updated: 2026-03-18 — Hervé SCHOENENBERGER*
*Next review: 2026-06-17*