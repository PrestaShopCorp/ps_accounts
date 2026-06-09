# Accounts — CLAUDE.md

---

## 1. Squad context

**Squad:** Squad Account
**Functional domain:** Token generation, OAuth2 authentication flow, shop identification/verification, synchronization with PrestaShop Cloud
**Main stack:** PHP 5.6–8.3 / Node.js / TypeScript / Vue 3 / Vite / PrestaShop 1.6–9.x
**Architecture pattern:** CQRS in `src/Account/` · legacy controllers in `controllers/`

---

## 2. Project structure

| Path                                | Purpose                                                              |
|-------------------------------------|----------------------------------------------------------------------|
| `ps_accounts.php`                   | Module entry point, hook registration, DI bootstrap                  |
| `src/Service/PsAccountsService.php` | Main public API consumed by other modules                            |
| `src/Service/OAuth2/`               | OAuth2 flow, refresh logic, token storage                            |
| `src/Account/`                      | CQRS: commands/queries for account and session state                 |
| `src/Account/Session/`              | Firebase shop/owner session management                               |
| `src/Controller/Admin/`             | Back-office controllers                                              |
| `src/Api/Client/`                   | HTTP clients for external APIs (accounts-api, billing)               |
| `src/Repository/`                   | DB access layer (PrestaShop ObjectModel)                             |
| `src/Hook/`                         | PrestaShop hook handlers                                             |
| `src/Installer/`                    | Module install/uninstall/upgrade logic                               |
| `src/ServiceContainer/`             | Lightweight-container service providers                              |
| `src/Http/`                         | Internal curl-based HTTP client                                      |
| `sql/`                              | SQL migration scripts                                                |
| `translations/`                     | Module translations                                                  |
| `views/`                            | Smarty templates, assets, compiled CSS/JS                            |
| `templates/`                        | Twig templates                                                       |
| `upgrade/`                          | Module upgrade scripts                                               |
| `controllers/`                      | Legacy controllers                                                   |
| `_dev/apps/`                        | TypeScript/Vue frontend (compiled to `views/`)                       |
| `config/`                           | YAML definitions for PrestaShop core integration (routing, services) |
| `tests/src/Unit/`                   | Unit tests                                                           |
| `tests/src/Feature/`                | Feature / integration tests                                          |

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
- Lightweight DI container (`prestashopcorp/lightweight-container`) — service providers declared in `src/ServiceContainer/`
- HTTP client: raw curl only (no Guzzle, no PSR-18)
- DB access exclusively through Repositories

**Anti-patterns to avoid:**
- No direct modification of `ps_configuration` — go through Repositories
- No direct DB calls in handlers — go through Repositories
- No `use Symfony\` in the module core code
- No `use GuzzleHttp\` or PSR-18 — internal curl client only
- Never reference unscoped vendor namespaces

---

## 4. PrestaShop 1.6 → 9 / PHP 5.6 → 8.3 compatibility 🔀

The module ships **one codebase** that must run from **PS 1.6 (PHP 5.6, no Symfony)** up to **PS 9 (PHP 8.x, Symfony container, module-as-app)**. This is the single most pervasive constraint in `src/` — every change must hold across the whole range.

### Golden rules

- **Write to the lowest common denominator (PHP 5.6).** No typed properties, return types, union types, arrow functions, named arguments, `??=`, spread in calls, etc. CI runs a **PHP 5.6 syntax checker** on all of `src/` — it will reject PHP 7+ syntax.
- **Never assume Symfony exists.** PS 1.6 has no Symfony container, router, or session. Core code goes through the module's own lightweight container, not the PS core container (see below).
- **Branch on version explicitly, never implicitly.** Use `version_compare(_PS_VERSION_, 'X', '>=')`. Don't rely on a class/method merely existing unless you `method_exists()`/`class_exists()`-guard it.
- **Test the edges, not just the middle.** A change that works on PS 8.1 can break PS 1.6 (syntax/Symfony) or PS 9 (upgrade/AST). Run the relevant platform presets.

### Version detection — where & how

- **`_PS_VERSION_`** + `version_compare()` is the canonical check, used throughout `src/`.
- `src/Context/ShopContext.php` — helpers `isShop17()`, `isShop173()`.
- `src/Log/Logger.php` — log path varies per version (`/log/` < 1.7 · `/app/logs/` 1.7.0–1.7.3 · `/var/logs/` ≥ 1.7.4).
- `src/Hook/ActionAdminLoginControllerSetMedia.php` — split `executeV8()` / `executeV9()` paths.
- `src/Service/OAuth2/OAuth2Client.php` — PS 9+ specific handling.
- `src/Service/UpgradeService.php` — `getVersion()` reads module config then core `ps_module.version`, with a legacy fallback.

### Polyfills — `src/Polyfill/`

These paper over PS-version API gaps; reuse them instead of branching ad hoc:

- `Traits/Controller/AjaxRender.php` — `ajaxRender()` (PS 1.7+) vs `ajaxDie()` (PS < 1.7).
- `Traits/AdminController/IsAnonymousAllowed.php` — `isAnonymousAllowed()` is **public on PS 9+**, **protected on PS < 9**; the right trait is selected at load time via `version_compare`.
- `ConfigurationStorageSession.php` — Symfony-less session storage backed by the configuration table (mainly PS 1.6).

### Container / DI

- The module's own **`PsAccountsContainer`** (`src/ServiceContainer/`, lightweight-container) is the primary DI mechanism on **all** versions — it does not depend on Symfony, so it works on PS 1.6.
- `ps_accounts.php`: `getCoreServiceContainer()` tries the PS core container (1.7+); `getServiceContainer()` returns the module container (always available). Prefer the module container in core code.
- `config/*.yml` (routes/services) is only honored on PS 1.7+ (Symfony-aware) and silently ignored on PS 1.6.

### PHP / vendor scoping

- `composer.json` pins `platform.php = 5.6`; tests use `tests/composer56.json` (PHPUnit ^5.5), `tests/composer71.json`, and the default.
- All vendors are scoped to `PrestaShop\Module\PsAccounts\Vendor\*` via php-scoper (`scoper.inc.php`) — Symfony polyfills are intentionally **not** scoped. Some deps are pinned old for PHP 5.6 (e.g. Sentry client — see `src/Service/SentryService.php`).

### PS 9 upgrade hazards ⚠️ (highest-risk area)

PS 9 parses a module's version via **static AST** and **never instantiates the module class** during upgrade (PS 8 does instantiate it). This breaks two assumptions — see `doc/diagnosis-ps9-upgrade-servicenotfound.md` and `doc/module-to-app.md`:

1. **`ServiceNotFound`** — a service provider already loaded earlier in the request (same FQCN across v7/v8) can't be reloaded mid-upgrade, so the container can miss newly-added handlers. Mitigated via autoload reset (`src/enforce_autoload.php`) + recovery/banner.
2. **Stale `VERSION` const** — `UpgradeService::setVersion()` must not read `\Ps_accounts::VERSION` (stale in memory on PS 9); thread the explicit target version from the upgrade script into the handler instead. `PS_ACCOUNTS_LAST_UPGRADE` being stale is the symptom.

When touching `upgrade/` or migration handlers, **always validate on the PS 9 platform** (`9.1.0-8.5`), not only PS 8.

### Version coverage matrix

| | PS 1.6 | PS 1.7 | PS 8 | PS 9 |
|---|---|---|---|---|
| PHP (CI) | 5.6 / 7.1 | 7.4 | 7.4 | 8.x |
| Symfony container/router | ✗ | ✓ | ✓ | ✓ |
| Module instantiated on upgrade | ✓ | ✓ | ✓ | ✗ (AST only) |
| Upgrade risk | low | moderate | moderate | **high** |
| Test composer | `composer56` / `composer71` | `composer71` | `composer71` | default |

CI (`accounts-qc-php.yml`) runs PHPUnit against `1.6.1.24-5.6`, `1.6.1.24-7.1`, `1.7.8.5-7.4`, `8.1.5-7.4`, `9.1.0-8.5`, and `nightly`. Use the matching `make platform-<ps>-<php>` preset locally.

---

## 5. Restricted areas 🚫

- `src/Service/OAuth2/` and `src/Account/Session/` — authentication/sessions: any modification requires mandatory review
- `sql/` — SQL migrations: never generate automatically
- `upgrade/` — upgrade scripts: risk of regression on existing shops
- `src/Service/PsAccountsService.php` — public API consumed by third-party modules, BC breaks forbidden
- Scoped vendors (`vendor/`, `dist/`) — do not modify manually

**If Claude proposes modifying a restricted area:** ask it to explain the alternative without touching that area.

---

## 6. Tests 🧪

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

## 7. Development workflow

**Branches:** `feature/[ticket-id]-description` · `fix/[ticket-id]-description` · no direct commits to `main`
**Commit format:** `feat(scope): description` · `fix(scope): description` (conventional commits)
**PR:** 
- name must be prefixed with `[ticket-id]` (e.g. `[ACC-1234] feat(scope): description`)
- mandatory review before merge
- NEVER create a PR for a security fix

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

### CI pipelines (`.github/workflows/`)

- **accounts-qc-php.yml** — PHP quality checks: syntax (5.6, 7.2, 7.3, 8.1, 8.3), PHP-CS-Fixer, header-stamp, PHPStan, PHPUnit across multiple PS versions (1.6.1.24, 1.7.8.5, 8.1.5, nightly)
- **build-release-publish.yml** — Version bump, php-scoper, front build, artifact creation, GCS upload (prod/preprod), marketplace publishing

**Before proposing a change, Claude must:**
1. Run unit tests for the modified area
2. Verify no restricted area is impacted
3. Propose the corresponding test if it does not exist

---

## 8. Business glossary

| Term                    | Definition                                                                                                                                                   |
|-------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
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

## 9. What Claude does well in this project ✅

- Generate CQRS handlers from an existing Command (follow the pattern in `src/Account/CommandHandler/`)
- Write PHPUnit unit tests for repositories and services
- Analyze the OAuth2 flow and explain token exchanges
- Identify BC impacts of a change on `PsAccountsService`
- Adapt code to maintain PHP 5.6 compatibility

---

## 10. What always requires human review ⚠️

- Any public interface modification of `PsAccountsService` (potential BC break for third-party modules)
- Generation or modification of SQL migrations (`sql/`)
- Changes to session management or OAuth2 authentication
- Modifications to upgrade scripts (`upgrade/`)
- Any change to the DI container service providers (`src/ServiceContainer/`)
- Updating or adding vendor dependencies (impacts on php-scoper scope)
- Any change to `upgrade/` scripts or migration handlers — must be validated on the PS 9 platform (AST/stale-const hazards, see §4)

---

*Last updated: 2026-06-04 — Hervé SCHOENENBERGER*
*Next review: 2026-09-04*
