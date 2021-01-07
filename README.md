# PrestaShop Account
## Introduction

The module **ps_accounts** is the interface between your module (PSx / Community Service) and PrestaShop's Accounts service.
- It manages the shop link and unlink to a user account.
- It can receives informations from PrestaShop's Accounts API to update data about user and/or shop authentication and verification.
- It can by query by AJAX calls from your module

Your PSx or Community Service needs to call this module in order to use PrestaShop Accounts service.

### Work as Community Service or PrestaShop X modules (PSx)

Your module needs three parts:

- [PS Accounts module](http://github.com/PrestaShopCorp/ps_accounts)
  - It's your interface between your module and PrestaShop Accounts service.
- [PrestaShop Accounts Vue Components](http://github.com/PrestaShopCorp/prestashop_accounts_vue_components)
  - It's the front-end component you need to integrate into your module.
- [Composer Library](http://github.com/PrestaShopCorp/prestashop_accounts_installer)
  - This library's role is here to compensate a lack of security between modules dependencies. If PS Accounts is removed while your module is still installed: it causes a crash of the PrestaShop module's page/feature.
  - This library is here to install automatically PS Accounts if it's missing.

## Installation

If you need to install and test a module, [you can download the desired zip here](https://github.com/PrestaShopCorp/ps_accounts/releases)
- ps_accounts.zip is the "production ready zip"
- ps_accounts_integration.zip is the zip you need if you want to test on the integration environment.

## How to start working with PS Accounts as a PSx or Community Service developer?
:warning: TODO: 
- The only env variable (ex: `PS_ACCOUNTS_ENV`), talk about configuration files (config_dev.yml, config_integration.yml, config_production.yml, une par défaut config.dist.yml?)
- L'obligation de passer par le service container, pas d'appel aux classes directement (never use `use PrestaShopAccounts\Namespace\Class)
- Exemple de code à intégrer pour utiliser le service container de PS Accounts
- Introduire le composant VueJS et rediriger vers la doc de ce dernier

## CI

CI trigger on pull request labeled 'quality assurance needed'

To set custom checkout branch , edit [custom-checkout-version](custom-checkout-version)

## Testing
:warning: TODO Vérifier avec @hSchoenenberger


## JWT
### What are JWTs?
:warning: TODO: explication sur la diff entre un JWT user (SSO) et JWT shop + diff entre un Firebase ID Token, Firebase Custom Token et Firebase Refresh token

### How to refresh the JWT
:warning: TODO : Le call à effectuer sur le module pour obtenir un nouveau JWT

## Breaking Changes
:warning: TODO: Expliquer ce qui sera différent avec les versions précédentes. La dépréciation de la lib composer prestashop_accounts_auth, la nécessité de prestashop_accounts_installer, les raisons de ce choix (pour éviter qu'on revienne en arrière)

## Troubleshoot
:warning: TODO: problème récurrent

