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
| `controllers/admin/AdminOAuth2PsAccountsController.php` (PS < 9) | `renderSameSiteBounce()` → `echo … ; exit;` |
| `src/Controller/Admin/OAuth2Controller.php` (PS 9+) | `renderSameSiteBounce()` → `Response` |

## Validation & point d'attention

Le correctif repose sur une hypothèse : **le cookie de session d'origine survit dans le
navigateur et est renvoyé sur le rebond same-site.** Sur Symfony, le callback lit la session
(`getShopId()`, `has('oauth2state')`) **avant** de décider de rebondir, ce qui démarre une session
vide ; le risque théorique était qu'un `Set-Cookie` parasite écrase le cookie d'origine et fasse
échouer le rebond.

➡️ **Validé en E2E sur PrestaShop 9 + `Strict` : le flux aboutit.** PrestaShop ne réémet pas de
`Set-Cookie` destructeur sur la session vide lue au premier passage, donc le cookie d'origine est
bien renvoyé sur le rebond.

Lors de la revue, sanity-check rapide possible sur les autres versions (legacy PS 1.6 a priori
sûr : pas de `$cookie->write()` avant `exit`). Si un jour un écrasement apparaissait sur une
version, la parade serait de rebondir **avant** de toucher la session (PS9 :
`$request->hasPreviousSession()`).

## Recommandation marchand

`Lax` reste le réglage **recommandé** (et le défaut PrestaShop). `Strict` est désormais
**supporté** grâce au rebond, au prix d'une redirection supplémentaire invisible pour
l'utilisateur.
