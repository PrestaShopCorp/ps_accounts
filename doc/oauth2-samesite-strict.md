# OAuth2 & le réglage `Cookie SameSite` de PrestaShop

## Symptôme

Quand un marchand règle **Paramètres avancés > Administration > Paramètres généraux >
Cookie SameSite = `Strict`** (configuration `PS_COOKIE_SAMESITE`), les flux OAuth2 du module
échouent :

- **Login BO** via PrestaShop Account.
- **Identification du point de contact** (`action=identifyPointOfContact`).
- Plus généralement tout flux qui repart d'auth-hydra vers le back-office.

En `Lax` (valeur **par défaut** de PrestaShop) ou `None` (+ HTTPS/`Secure`), tout fonctionne.

## Cause

Le flux OAuth2 (authorization code + PKCE) stocke `oauth2state` et `oauth2pkceCode` **dans la
session BO** avant de rediriger vers auth-hydra
(`src/AccountLogin/OAuth2LoginTrait.php`, `oauth2Redirect()`).

Au retour, auth-hydra renvoie un `302` vers notre callback (`ps_accounts_oauth2` /
`AdminOAuth2PsAccountsController`). C'est une **navigation top-level cross-site** :

| `PS_COOKIE_SAMESITE` | Cookie admin envoyé sur ce retour ? | Résultat |
|----------------------|-------------------------------------|----------|
| `Lax` (défaut)       | Oui (navigation GET top-level)      | OK       |
| `None` (+ Secure)    | Oui                                 | OK       |
| `Strict`             | **Non**                             | Session vide → flux cassé |

Avec `Strict`, le navigateur **retient** le cookie de session. Le callback s'exécute donc sur une
session **vide** : `oauth2state`/`oauth2pkceCode` ont disparu. Sans le `code_verifier`, l'échange
de token renvoyait un `invalid_grant` opaque ; pour le login/point de contact, le cookie employé
n'est pas non plus renvoyé, donc le callback s'exécute sans employé.

On ne peut pas réparer côté serveur une requête entrante qui ne porte pas le cookie — **sauf** en
la rejouant en *same-site*, contexte où `Strict` autorise l'envoi du cookie.

## Correctif : rebond same-site

Lorsque le callback détecte une session perdue (`!$session->has('oauth2state')` avec un `code` et
un `state` présents dans l'URL), au lieu d'échouer, le module sert une petite page HTML
**same-origin** qui re-navigue immédiatement (`window.location.replace`) vers **la même URL** de
callback — paramètres `code`/`state` préservés, plus un marqueur one-shot `__ps_oauth_retry=1`.

Cette seconde navigation est **same-site** → le navigateur envoie le cookie `Strict` → la session
est restaurée → le flux se déroule normalement.

```
auth-hydra ──302──▶ callback (cross-site, cookie Strict NON envoyé → session vide)
                        │
                        ▼ page HTML same-origin : location.replace(url + &__ps_oauth_retry=1)
                    callback (SAME-SITE, cookie Strict envoyé → session restaurée) ──▶ flux OK
```

### Garde anti-boucle

Si le marqueur `__ps_oauth_retry=1` est **déjà** présent et que la session est **toujours** vide,
c'est une perte de session réelle (session expirée, cookies désactivés…) : le module échoue
proprement sur `Invalid state`, sans boucler. **Au plus un hop supplémentaire.**

### Préservation du cookie (Symfony, PS < 9)

Le rebond est une page **cul-de-sac qui ne doit poser aucun cookie** : le navigateur doit conserver
ses cookies d'origine et les renvoyer au replay same-site. Sur le retour cross-site sans cookie,
deux écritures de cookie parasites doivent être neutralisées dans
`AdminOAuth2PsAccountsController::renderSameSiteBounce()` :

1. **Cookie de session Symfony** — `Ps_accounts::getSession()` renvoie la session cœur ; le premier
   accès (`getShopId()`, puis `has('oauth2state')`) déclenche `session_start()`, qui crée une
   session vide et émet aussitôt un `Set-Cookie` (nouvel id). On le retire avec
   `header_remove('Set-Cookie')`.
2. **Cookie admin (employé)** — le cookie employé n'étant pas envoyé, PrestaShop construit un
   `Cookie` **anonyme** dont le `write()` s'exécute sur `__destruct` (donc pendant `exit`, **après**
   `header_remove`, et avec l'output buffering du BO `headers_sent()` est encore faux → le
   `setcookie()` réussit) : il **réémet un cookie admin anonyme qui écrase le vrai** → employé
   déconnecté à la page suivante. On bloque cette écriture avec
   `Context::cookie->disallowWriting()` (méthode cœur, gardée par `method_exists`).

Sans (2), le **login** s'en remettait (il réécrit un cookie employé neuf via `$cookie->write()`),
mais le **point de contact** restait déconnecté (il ne réécrit jamais le cookie employé). PS 9
(réponse Symfony, pas d'`exit`) n'est pas concerné.

### Sécurité

- Le `code` n'est **jamais** échangé sans un `oauth2state` de session correspondant.
- Le contrôle CSRF (`$state !== $session->get('oauth2state')`) reste actif après restauration.
- L'URL de rebond est échappée (`json_encode` côté JS, `htmlspecialchars(..., ENT_QUOTES)` côté
  `<noscript>` meta-refresh) car elle reflète des paramètres d'URL.
- Le déclenchement est **piloté par le symptôme** (session absente + `code`/`state` présents), pas
  par la lecture de `PS_COOKIE_SAMESITE` — ce qui couvre aussi d'autres causes de cookie non
  renvoyé.

## Implémentation

| Fichier | Rôle |
|---------|------|
| `src/AccountLogin/OAuth2LoginTrait.php` | Détection (`oauth2Login()`), `hasAlreadyBounced()`, `buildBounceUrl()`, `buildBounceHtml()`, abstrait `renderSameSiteBounce()` |
| `controllers/admin/AdminOAuth2PsAccountsController.php` (PS < 9) | `renderSameSiteBounce()` → `disallowWriting()` + `header_remove('Set-Cookie')` + `echo … ; exit;` |
| `src/Controller/Admin/OAuth2Controller.php` (PS 9+) | `renderSameSiteBounce()` → `Response` |

## État par flux et par version (`Strict`)

| Flux | PS 9 | PS 8 / 1.7 | PS 1.6 |
|------|------|------------|--------|
| **Login BO** | ✅ | ✅ (rebond + purge `Set-Cookie`) | à valider |
| **Point de contact** | ✅ | ✅ (session cœur + clear ciblé) — **à valider en E2E** | à valider |

- **Login** : l'état OAuth vit dans la session cœur ; le rebond + la purge du `Set-Cookie`
  préservent le cookie de session, qui repart au replay same-site.
- **Point de contact** : voir la section suivante.

## Point de contact : session cœur + clear ciblé (PS 1.7+)

### Le problème historique

`AdminOAuth2PsAccountsController::getSession()` renvoyait `ConfigurationStorageSession` (store DB,
indexé par `id_fallback_session` porté par le cookie employé) **dès que `Context::employee->id`
était défini** — sur **toutes** les versions, pas seulement PS 1.6. Cette fallback était la
**raison d'être** du bon fonctionnement du point de contact malgré le `clear()` final :
`redirectAfterLogin()` appelait `$this->getSession()->clear()` en fin de flux ; avec la session
cœur, ce `clear()` aurait **vidé la session BO de l'employé** → déconnexion à la page suivante. La
fallback isolait donc l'état OAuth dans un store séparé pour protéger la session BO.

Mais sous `Strict`, cette fallback **empêche le rebond** : au retour cross-site le cookie employé
n'est pas envoyé → `employee->id == 0` → `getSession()` ne retombe pas sur le store DB, et la
récupération n'aboutit pas (le point de contact restait KO sur PS 8 / 1.7).

### La solution

Découpler les deux responsabilités :

1. **Clear ciblé** — `redirectAfterLogin()` appelle désormais `clearOAuth2SessionState()`
   (`OAuth2LoginTrait`) au lieu de `getSession()->clear()`. Cette méthode ne retire **que** les clés
   transitoires OAuth (`oauth2state`, `oauth2pkceCode`, `oauth2action`, `source`, `shopId`,
   `forceSignup`, `return_to`) et laisse le reste de la session intact → la session BO survit, même
   quand `getSession()` est la session cœur.
2. **Fallback re-scopée à PS 1.6** — `getSession()` renvoie simplement `module->getSession()`, qui
   rend `ConfigurationStorageSession` **uniquement sur PS 1.6** (pas de conteneur cœur) et la session
   cœur sur PS 1.7+. Le point de contact utilise donc la **même** session que le login sur PS 1.7+,
   ce qui rend le **rebond opérant** pour les deux flux. PS 1.6 n'est pas concerné par SameSite=Strict.

Effet de bord corrigé au passage : `ConfigurationStorageSession::remove()` écrivait dans une clé de
configuration nommée d'après l'attribut au lieu de la ligne de session (`getConfigurationName()`) —
corrigé pour que le clear ciblé fonctionne aussi sur PS 1.6.

> PS 9 (`OAuth2Controller`) ne fait pas de `clear()` final et n'utilise pas la fallback : inchangé.

## Recommandation marchand

`Lax` reste le réglage **recommandé** (et le défaut PrestaShop). En `Strict`, **login** et
**point de contact** sont désormais supportés via le rebond same-site (redirection invisible). À
**valider en E2E** sur PS 8 / 1.7 (les deux flux, point de contact dans la popup) avant de
communiquer le support de `Strict`.
