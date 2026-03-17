# Accounts — CLAUDE.md

> Ce fichier est lu par Claude Code à chaque session. Il remplace toute configuration ad hoc.

---

## 1. Contexte de la squad

**Squad :** Squad Account
**Domaine fonctionnel :** Génération de tokens, flow d'authentification OAuth2, identification/vérification de boutiques, synchronisation avec PrestaShop Cloud
**Stack principale :** PHP 5.6–8.3 / Node.js / TypeScript / Vue 3 / Vite / PrestaShop 1.6–9.x
**Pattern d'architecture :** CQRS dans `src/Account/` · legacy controllers dans `controllers/`

---

## 2. Structure du projet

| Path                                | Purpose                                                            |
|-------------------------------------|--------------------------------------------------------------------|
| `ps_accounts.php`                   | Point d'entrée du module, registration des hooks, bootstrap DI     |
| `src/Service/PsAccountsService.php` | API publique principale consommée par les autres modules           |
| `src/Service/OAuth2/`               | Flow OAuth2, refresh logic, stockage des tokens                    |
| `src/Account/`                      | CQRS : commandes/queries pour l'état du compte et des sessions     |
| `src/Account/Session/`              | Gestion des sessions Firebase shop/owner                           |
| `src/Controller/Admin/`             | Contrôleurs du back-office                                         |
| `src/Controller/Front/`             | Contrôleurs front-office / API                                     |
| `src/Api/V2/`                       | Endpoints REST API v2                                              |
| `src/Repository/`                   | Couche d'accès DB (PrestaShop ObjectModel)                         |
| `src/Hook/`                         | Handlers de hooks PrestaShop                                       |
| `src/Installer/`                    | Logique install/uninstall/upgrade du module                        |
| `src/ServiceContainer/`             | Setup du conteneur DI et service providers                         |
| `src/Http/`                         | Client HTTP interne basé sur curl                                  |
| `sql/`                              | Scripts de migration SQL                                           |
| `translations/`                     | Traductions du module                                              |
| `views/`                            | Templates Smarty, assets, CSS, JS compilé                          |
| `templates/`                        | Templates Twig                                                     |
| `upgrade/`                          | Scripts d'upgrade du module                                        |
| `controllers/`                      | Contrôleurs legacy                                                 |
| `_dev/apps/`                        | Frontend TypeScript/Vue (compilé vers `views/`)                    |
| `config/`                           | Définitions YAML services/routes (routing PrestaShop/Symfony)      |
| `tests/src/Unit/`                   | Tests unitaires                                                    |
| `tests/src/Feature/`                | Tests feature / intégration                                        |

**Point d'entrée principal :** `ps_accounts.php`
**Fichiers de config critiques :** `config.php` (généré depuis `config.dist.php`, gitignored)

### Flux de données

```
Autres modules → PsAccountsService
                      ↓
             OAuth2Service (gestion des tokens)
                      ↓
             Repository (DB : shop UUID, tokens, refresh tokens)
                      ↓
             Externe : accounts-api / auth-hydra (serveur OAuth2)
```

---

## 3. Conventions de code ⚡

**Nommage :**
- Commandes CQRS : `[Action][Entité]Command` — ex: `CreateIdentityCommand`
- Handlers CQRS : `[Action][Entité]Handler` — ex: `CreateIdentityHandler`
- Tests : `[ClasseTestée]Test.php` — méthodes annotées `@test`, nommées `itShould[Action][Context]` (pas de préfixe `test`)

**Contraintes PHP obligatoires :**
- **PHP 5.6 compatible** pour tout le code `src/` — pas de typed properties, union types, named arguments, ni syntaxe PHP 7+
- Toutes les dépendances vendor tierces sont scopées sous `PrestaShop\Module\PsAccounts\Vendor\*` via php-scoper — **ne jamais référencer des namespaces vendor non-scopés**
- Tout nouveau fichier PHP doit porter le header de licence AFL-3.0 (vérifié par `header-stamp`)

**Patterns utilisés :**
- Conteneur DI léger (`prestashopcorp/lightweight-container`) — services déclarés dans `config/services.yml`
- Client HTTP : curl brut uniquement (pas de Guzzle, pas de PSR-18)
- Accès DB exclusivement via les Repositories

**Anti-patterns à éviter :**
- Pas de modification directe de `ps_configuration` — passer par les Repositories
- Pas d'appels directs à la DB dans les handlers — passer par les Repositories
- Pas de `use Symfony\` dans le code core du module
- Pas de `use GuzzleHttp\` ni de PSR-18 — client curl interne uniquement
- Ne pas référencer des namespaces vendor non-scopés

---

## 4. Zones interdites 🚫

- `src/Service/OAuth2/` et `src/Account/Session/` — authentification/sessions : toute modification = review obligatoire
- `sql/` — migrations SQL : ne jamais générer automatiquement
- `upgrade/` — scripts d'upgrade : risque de régression sur les boutiques existantes
- `src/Service/PsAccountsService.php` — API publique consommée par des modules tiers, BC break interdit
- `config/prod/` — configuration production
- Vendors scopés (`vendor/`, `dist/`) — ne pas modifier manuellement

**Si Claude propose de modifier une zone interdite :** lui demander d'expliquer l'alternative sans toucher à cette zone.

---

## 5. Tests 🧪

**Framework :** PHPUnit (compatible PHP 5.6–8.x)

**Prérequis :** Docker doit être lancé avec une plateforme de test active.

```bash
# Démarrer une plateforme de test (Docker + PrestaShop + install module)
make platform-8.1.5-7.4   # PS 8.1.5 sur PHP 7.4 (le plus courant)
make platform-8.2.0-8.1   # PS 8.2.0 sur PHP 8.1

# Lancer tous les tests (unit + feature)
make phpunit

# Tests unitaires uniquement
make phpunit-run-unit

# Tests feature / intégration uniquement
make phpunit-run-feature

# Combo : démarrer la plateforme ET lancer les tests
make phpunit-8.1.5-7.4

# Lancer un test ou une classe spécifique dans le container
docker exec -w /var/www/html/modules/ps_accounts/tests phpunit \
  ./vendor/bin/phpunit --filter TestClassName

# Installer les dépendances de test
env COMPOSER=composer56.json php ./composer.phar install --working-dir=./tests/
```

**Localisation des tests :**
- `tests/src/Unit/` — tests unitaires (miroir de `src/`)
- `tests/src/Feature/` — tests feature/intégration

**Convention de nommage des tests :** `[ClasseTestée]Test.php`, méthodes annotées `@test`, nommées `itShould[Action][Context]` (pas de préfixe `test`)

---

## 6. Workflow de développement

**Branches :** `feature/[ticket-id]-description` · `fix/[ticket-id]-description` · pas de commit direct sur `main`
**Format de commit :** `feat(scope): description` · `fix(scope): description` (conventional commits)
**PR :** review obligatoire avant merge

```bash
# Qualité du code
make php-cs-fixer-test    # Vérification style (dry-run)
make php-cs-fixer         # Correction automatique du style
make header-stamp-test    # Validation des headers de licence AFL
make phpstan              # Analyse statique (tourne dans Docker)

# Build frontend
make build-front
# Équivalent à :
pnpm --filter ./_dev install --frozen-lockfile --ignore-scripts
pnpm --filter ./_dev build

# Bundling
make bundle          # Bundle complet : php-scoper + config + front → ps_accounts.zip
make bundle-prod     # Bundle production
make bundle-preprod  # Bundle pré-production

# Dépendances PHP
./scripts/composer-install.sh               # Installer composer si absent
php ./composer.phar install --prefer-dist -o --no-dev  # Deps production
```

**Avant de proposer un changement, Claude doit :**
1. Lancer les tests unitaires de la zone modifiée
2. Vérifier qu'aucune zone interdite n'est impactée
3. Proposer le test correspondant si non existant

---

## 7. Glossaire métier

| Terme                   | Définition                                                                                                                                                   |
|-------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Shop UUID / CloudShopId | Identifiant unique de la boutique PrestaShop côté accounts-api                                                                                               |
| TokenV2                 | Token OAuth2 shop émis par auth-hydra : JWT access_token + refresh_token opaque. Stocké en config (`PS_ACCOUNTS_ACCESS_TOKEN`) via `ConfigurationRepository` |
| OAuth2Client            | Credentials de la boutique (clientId / clientSecret) utilisés pour obtenir un TokenV2 via client credentials flow                                            |
| Owner token             | Token Firebase de l'administrateur propriétaire du compte — **déprécié**                                                                                     |
| Shop token              | Token Firebase de la boutique (distinct de l'owner token) — **déprécié**, remplacé par `TokenV2`                                                             |
| accounts-api            | API externe PrestaShop Cloud qui gère les comptes marchands                                                                                                  |
| auth-hydra              | Serveur OAuth2 PrestaShop (Ory Hydra) qui émet les TokenV2                                                                                                   |
| Identity                | Représentation d'une boutique identifiée côté accounts — voir `src/Account/`                                                                                 |
| Proof                   | Mécanisme de vérification de propriété de la boutique (`ProofManager`)                                                                                       |
| Session                 | Abstraction de session shop — `src/Account/Session/ShopSession.php` (OAuth2, active) · `src/Account/Session/Firebase/` (déprécié)                            |
| Command                 | Commande CQRS (ne pas confondre avec les commandes PrestaShop)                                                                                               |
| Scoped vendor           | Namespace vendor préfixé `PrestaShop\Module\PsAccounts\Vendor\*` via php-scoper                                                                              |

---

## 8. Ce que Claude fait bien dans ce projet ✅

- Générer des handlers CQRS à partir d'une Command existante (suivre le pattern `src/Account/CommandHandler/`)
- Écrire des tests unitaires PHPUnit pour les repositories et services
- Analyser le flow OAuth2 et expliquer les échanges de tokens
- Identifier les impacts BC d'un changement sur `PsAccountsService`
- Adapter le code pour maintenir la compatibilité PHP 5.6

---

## 9. Ce qui demande toujours une validation humaine ⚠️

- Toute modification d'interface publique de `PsAccountsService` (BC break potentiel pour les modules tiers)
- Génération ou modification de migrations SQL (`sql/`)
- Changements dans la gestion des sessions ou de l'authentification OAuth2
- Modifications des scripts d'upgrade (`upgrade/`)
- Tout changement de la configuration du conteneur DI (`config/services.yml`)
- Mise à jour ou ajout de dépendances vendor (impacts sur le scope php-scoper)

---

*Dernière mise à jour : 2026-03-17 — Hervé SCHOENENBERGER*
*Prochain review : 2026-06-17*
