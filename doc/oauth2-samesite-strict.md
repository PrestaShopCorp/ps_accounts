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

Sur Symfony (PS 1.7/8), `Ps_accounts::getSession()` renvoie la **session cœur** (service
`session`). Au premier accès (`getShopId()`, puis `has('oauth2state')`), `session_start()` est
appelé : sur le retour cross-site sans cookie, il **crée une session vide et émet aussitôt un
`Set-Cookie`** (nouvel id) qui **écraserait le cookie d'origine** → le rebond same-site repartirait
alors avec un cookie vide. Pour l'éviter, `AdminOAuth2PsAccountsController::renderSameSiteBounce()`
**purge tout `Set-Cookie` en attente** (`header_remove('Set-Cookie')`) avant d'émettre la page de
rebond : c'est une page cul-de-sac qui ne doit poser aucun cookie, donc le navigateur conserve ses
cookies d'origine et les renvoie au replay. PS 9 (réponse Symfony) n'est pas concerné.

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
| `controllers/admin/AdminOAuth2PsAccountsController.php` (PS < 9) | `renderSameSiteBounce()` → `header_remove('Set-Cookie')` + `echo … ; exit;` |
| `src/Controller/Admin/OAuth2Controller.php` (PS 9+) | `renderSameSiteBounce()` → `Response` |

## État par flux et par version (`Strict`, validé E2E)

| Flux | PS 9 | PS 8 / 1.7 | PS 1.6 |
|------|------|------------|--------|
| **Login BO** | ✅ | ✅ (rebond + purge `Set-Cookie`) | à valider |
| **Point de contact** | ✅ | ❌ **non résolu** (voir ci-dessous) | à valider |

- **Login** : OK sur PS 9, 8 et 1.7. L'état OAuth vit dans la session cœur ; le rebond + la purge
  du `Set-Cookie` préservent le cookie de session, qui repart au replay same-site.
- **Point de contact** : OK sur PS 9 ; **toujours KO sur PS 8 / 1.7** malgré le rebond — à cause de
  la fallback `ConfigurationStorageSession` (cf. section suivante).

## Limitation connue : point de contact sur PS 8 / 1.7

### Pourquoi le rebond ne suffit pas

En mode **connecté**, `AdminOAuth2PsAccountsController::getSession()` renvoie
`ConfigurationStorageSession` (store DB) **dès que `Context::employee->id` est défini** — et non la
session cœur. Cette fallback est **forcée pour le point de contact, pas seulement en PS 1.6**.

Au retour cross-site `Strict`, le cookie employé n'est pas envoyé → `employee->id == 0` →
`getSession()` retombe sur la session cœur (pas sur `ConfigurationStorageSession`) → rebond. Même
avec la purge du `Set-Cookie`, la **récupération du point de contact n'aboutit pas** sur PS 8 / 1.7
(mécanisme exact encore à confirmer ; l'état transitoire est indexé par `id_fallback_session`, porté
par le cookie employé — l'interaction avec le retour cross-site reste à élucider).

### Le compromis (et pourquoi on ne peut pas juste « ignorer la fallback »)

Si l'on **force la session cœur** pour le point de contact (au lieu de `ConfigurationStorageSession`),
le rebond **récupère bien** le point de contact sur PS 8 / 1.7 — **mais le marchand est déconnecté
à la page suivante** (le set point de contact passe, puis on retombe sur le login).

Cause : `redirectAfterLogin()` appelle `$this->getSession()->clear()` en fin de point de contact
(`controllers/admin/AdminOAuth2PsAccountsController.php:213`). Avec la session cœur, ce `clear()`
**vide la session BO de l'employé** → déconnexion. C'est précisément **la raison d'être de la
fallback `ConfigurationStorageSession`** : isoler l'état OAuth transitoire dans un store DB séparé
pour que le `clear()` final ne détruise pas la session BO (`getSession()` L249-257 + commentaire
`// FIXME: fallback only for setPointOfContact` L252).

### Conséquence

Tant que cette tension n'est pas levée, sur PS 8 / 1.7 :
- soit on garde la fallback → BO préservé mais **point de contact KO en `Strict`** ;
- soit on l'ignore → point de contact OK en `Strict` mais **régression d'auth BO** (déconnexion).

Pistes pour une vraie solution (non implémentées) : ne `clear()` que l'état OAuth transitoire sans
toucher la session BO ; ou stocker l'état transitoire dans un store indépendant du cookie employé,
réhydratable au replay sans dépendre de `employee->id`.

## Recommandation marchand

`Lax` reste le réglage **recommandé** (et le défaut PrestaShop). En `Strict` : le **login** est
désormais supporté (rebond same-site, redirection invisible) ; le **point de contact** n'est **pas
encore supporté sur PS 8 / 1.7** (OK sur PS 9). À documenter côté support tant que la limitation
n'est pas levée.
