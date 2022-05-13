# PrestaShop Account

## Introduction

The module **ps_accounts** is the interface between your module (PSx / Community Service) and PrestaShop's Accounts service.
- It manages the shop link and unlink to a user account.
- It can receives informations from PrestaShop's Accounts API to update data about user and/or shop authentication and verification.
- It can be queried by AJAX calls from your module

Your PSx or Community Service needs to call this module in order to use PrestaShop Accounts service.

### Work as Community Service or PrestaShop X modules (PSx)

Your module needs three parts :

- [PS Accounts module](http://github.com/PrestaShopCorp/ps_accounts)
    - This module must be installed.
    - It's your interface between your module and PrestaShop Accounts service.
  
And in your PSx :

- [PS Accounts Installer (Composer Library)](http://github.com/PrestaShopCorp/prestashop-accounts-installer)
    - This library's role is here to compensate a lack of security between modules dependencies. If PS Accounts is removed while your module is still installed: it causes a crash of the PrestaShop module's page/feature.
    - This library is here to install automatically PS Accounts if it's missing.
    - It's your interface between your module and PrestaShop Accounts module
    - You should never require directly PrestaShop\Module\PsAccounts namespace classes
  
- [PrestaShop Accounts Vue Components](http://github.com/PrestaShopCorp/prestashop_accounts_vue_components)
    - It's the front-end component you need to integrate into your module's configuration page.
    - :warning: TODO:Introduce the VueJS component and redirect to its doc

## Installation

If you need to install and test the module, [you can download the desired zip here](https://github.com/PrestaShopCorp/ps_accounts/releases)
- **ps_accounts.zip** is the "**production** ready zip"
- **ps_accounts_integration.zip** is the zip you need if you want to test on the **integration environment**.

## How to start working with PS Accounts as a PSx or Community Service developer?

- [Read the official documentation here](https://devdocs.prestashop.com/1.7/modules/)
- Clone this repository
- Copy paste the `config/config.yml.dist` to `config/config.yml`

## Continous Integration

CI trigger on pull request labeled 'quality assurance needed'

To set custom checkout branch , edit [custom-checkout-version](custom-checkout-version)

## Testing

:warning: TODO To be verified with @hSchoenenberger

## JWT

### What are JWTs and how are they used?

JWT are [JSON Web Tokens](https://jwt.io/).

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

Here is a recap of the configuration variables used to manage a shop account

| ps_configuration                      | User account (Firebase SSO) | Shop (Firebase Ready) | What for ?
|---------------------------------------|-----|-------|---
| PS_ACCOUNTS_FIREBASE_ID_TOKEN         |     | X     | authenticate your shop, query accounts-api, billing-api...
| PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN    |     | X     |          
| PSX_UUID_V4                           |     | X     | identify your shop          
| PS_ACCOUNTS_FIREBASE_EMAIL            | X   |       | identify your account
| PS_ACCOUNTS_FIREBASE_EMAIL_IS_VERIFIED| X   |       |

### How to refresh the JWT

```php
use PrestaShop\PsAccountsInstaller\Installer\Installer;
use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;

define('MIN_PS_ACCOUNTS_VERSION', '4.0.0');

$facade = new PsAccounts(new Installer(MIN_PS_ACCOUNTS_VERSION));
$shopToken = $facade->getPsAccountsService()->getOrRefreshToken();
```

see: [PrestaShop Accounts Installer](http://github.com/PrestaShopCorp/prestashop-accounts-installer) for more details on how to setup Installer.

## Breaking Changes
### Removal of the environment variables
This module don't use a .env file as a configuration file. We are now using YAML files with a Symfony service container to configure services and their injected dependencies as well as configuration parameters.
You can copy and paste the `config.yml.dist` to `config.yml` but you **MUST NOT COMMIT THIS FILE**

### Composer dependency `prestashop_accounts_auth` deprecated
This library will is deprecated and no longer needed.
Please remove it from your module's dependencies.

### New composer dependency `prestashop-accounts-installer`
**Do not directly import PrestaShop Accounts classes**

If you need to call PrestaShop Accounts public classes's methods, you need to use the service container.

see: [PrestaShop Accounts Installer](http://github.com/PrestaShopCorp/prestashop-accounts-installer)

