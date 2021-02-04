# PrestaShop Account
## Introduction

The module **ps_accounts** is the interface between your module (PSx / Community Service) and PrestaShop's Accounts service.
- It manages the shop link and unlink to a user account.
- It can receives informations from PrestaShop's Accounts API to update data about user and/or shop authentication and verification.
- It can be query by AJAX calls from your module

Your PSx or Community Service needs to call this module in order to use PrestaShop Accounts service.

### Work as Community Service or PrestaShop X modules (PSx)

Your module needs three parts :

- [PS Accounts module](http://github.com/PrestaShopCorp/ps_accounts)
    - This module must be installed.
    - It's your interface between your module and PrestaShop Accounts service.
  
And in your PSx :

- [Composer Library](http://github.com/PrestaShopCorp/prestashop-accounts-installer)
    - This library's role is here to compensate a lack of security between modules dependencies. If PS Accounts is removed while your module is still installed: it causes a crash of the PrestaShop module's page/feature.
    - This library is here to install automatically PS Accounts if it's missing.
    - It's your interface between your module and PrestaShop Accounts module
    - You should never require directly PrestaShop\Module\PsAccounts namespace classes
  
- [PrestaShop Accounts Vue Components](http://github.com/PrestaShopCorp/prestashop_accounts_vue_components)
    - It's the front-end component you need to integrate into your module's configuration page.
    - :warning: Introduire le composant VueJS et rediriger vers la doc de ce dernier

## Installation

If you need to install and test the module, [you can download the desired zip here](https://github.com/PrestaShopCorp/ps_accounts/releases)
- **ps_accounts.zip** is the "**production** ready zip"
- **ps_accounts_integration.zip** is the zip you need if you want to test on the **integration environment**.

## How to start working with PS Accounts as a PSx or Community Service developer?

- [Read the official documentation here](https://devdocs.prestashop.com/1.7/modules/)
- Clone this repository
- Copy paste the `config/config.yml.dist` to `config/config.yml`

## CI

CI trigger on pull request labeled 'quality assurance needed'

To set custom checkout branch , edit [custom-checkout-version](custom-checkout-version)

## Testing
:warning: TODO Vérifier avec @hSchoenenberger

## JWT
### What are JWTs?
We use JWTs for 2 types of account: the user account and the shop account.
What we're identifying when we link a PrestaShop shop is **a shop**. A shop belongs to 1 owner (user).

There are 2 Firebase projects:
- **prestashop-newsso-production** is the Firebase Authentication project we're using to authenticate **users** _(prestashop-newsso-staging) for staging environment_
- **prestashop-ready-prod** is the Firebase Authentication project we're using to authenticate **shops** _(psessentials-integration) for integration environment_

**Don't try to verify a shop token against **prestashop-newsso-production** it won't work.**

There are 3 kinds of tokens that can interest you:
- Firebase ID Token 
- Firebase Custom Token
- Firebase Refresh token
[For more informations, please read the official documentation here](https://firebase.google.com/docs/auth/users#auth_tokens)

### How to refresh the JWT

```php
use PrestaShop\PsAccountsInstaller\Installer\Installer;

define('MIN_PS_ACCOUNTS_VERSION', '4.0.0');

$installer = new Installer(MIN_PS_ACCOUNTS_VERSION);
$shopToken = $installer->getPsAccountsService()->getOrRefreshToken();
```

## Breaking Changes
### Removal of the environment variables
This module don't use a .env file as a configuration file. We are now using YAML files with a Symfony service container to configure services and their injected dependencies as well as configuration parameters.
You can copy and paste the `config.yml.dist` to `config.yml` but you **MUST NOT COMMIT THIS FILE**

### Composer library prestashop_accounts_auth deprecated
This library will is deprecated and no longer needed.
Please remove it from your module's dependencies.

### Do not directly import PrestaShop Accounts classes
If you need to call PrestaShop Accounts public classes's methods, you need to use the service container.

**DO NOT USE**
```php
$psAccountsService = new \PrestaShop\Module\PsAccounts\Service\PsAccountsService();
```

**USE INSTEAD**
```php
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

/** @var PsAccountsService $psAccountsService */
$psAccountsService = Module::getInstanceByName('ps_accounts')->getService(PsAccountsService::class);
```

### Add the dependency manager library to your module's dependencies
If the end-user delete or force the uninstallation of the module `ps_accounts` without uninstalling a PSX or Community Service that depends of PS Accounts presence, the module page and feature will throw an exception.

The user will be stuck and we do not want that. 

In order to palliate to this PrestaShop problem, we need _something_ that checks if the module PS Accounts is installed. This something comes with the *prestashop-accounts-installer* library available on Packagist.

**YOU MUST ADD THIS DEPENDENCY TO YOUR composer.json**

---

## Troubleshoot
### `PS_ACCOUNTS_ENV` not found
You declare the environment variable without specifying a value. Delete the declaration or specify a valid value.

### The PrestaShop module page throws an error \PrestaShop\Namespace\Class not found
You didn't add the *prestashop-accounts-installer* to your composer.json dependencies and the ps_accounts module is not present but your module calls for it.
