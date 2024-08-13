# PrestaShop Account

[![Source Code](https://img.shields.io/badge/source-PrestaShopCorp/ps_accounts-blue.svg?style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts)
[![Latest Version](https://img.shields.io/github/release/PrestaShopCorp/ps_accounts.svg?style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts/releases)
[![Software License](https://img.shields.io/badge/license-OSL-brightgreen.svg?style=flat-square)](https://github.com/PrestaShopCorp/oauth2-prestashop/blob/main/LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/PrestaShopCorp/ps_accounts/.github/workflows/php.yml?label=CI&logo=github&style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts/actions?query=workflow%3ACI)

The module **ps_accounts** is the interface between your module and PrestaShop's services. It manages:

- Shop association and dissociation processes.
- Maintain secure communication between shop and Prestashop services.
- Synchronize basic informations about the shops (Shop Urls).

## Installation

If you need to install and test the module, [you can download the desired zip here](https://github.com/PrestaShopCorp/ps_accounts/releases).

### Compatibility Matrix

We aims to follow partially the Prestashop compatibility charts

- [Compatibility Chart Prestashop 1.6 & 1.7](https://devdocs.prestashop.com/1.7/basics/installation/system-requirements/#php-compatibility-chart)
- [Compatibility Chart Prestashop 8.0](https://devdocs.prestashop.com/8/basics/installation/system-requirements/#php-compatibility-chart)

| ps_accounts version | Prestashop Version | PHP Version   |
| ------------------- | ------------------ | ------------- |
| 7.x (unified)       | \>=1.6 && <9.x     | PHP 5.6 - 8   |
| 6.x                 | \>=8.0.0           | PHP 7.2 - 8   |
| 5.x                 | \>=1.6 && <8.0.0   | PHP 5.6 - 7.4 |

### Integration along with your Prestashop module

If you are integrating a module, you should have a look on the [PrestaShop Integration Framework Documentation](https://docs.cloud.prestashop.com/).

## APIs

Here are listed Open APIs provided by this module:

| HTTP Verb | Controller          | Method                  | Payload                     | Description                                                                                                                                                                          |
| --------- | ------------------- | ----------------------- | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| GET       | AdminAjaxPsAccounts | getOrRefreshAccessToken | { token: "<access_token>" } | Return a token provided by [Prestashop OpenId Connect Provider](https://oauth.prestashop.com/.well-known/openid-configuration) when the user has been authenticated by this provider |

Example: I want to get the authenticated user token in order make action on his behalf. The request would be `GET https://<shop-admin-url>/index.php?controller=AdminAjaxPsAccounts&action=getOrRefreshAccessToken&ajax=true&token=<token>` where `token` is a Prestashop Admin token.

## Custom hooks

Here are listed custom hooks provided with this module:

| Hook name                         | Payload          | Description                                          |
| --------------------------------- | ---------------- | ---------------------------------------------------- |
| actionShopAccountLinkAfter        | shopId, shopUuid | Triggered after link has been acknowledged by shop   |
| actionShopAccountUnlinkAfter      | shopId, shopUuid | Triggered after unlink has been acknowledged by shop |
| actionShopAccessTokenRefreshAfter | token            | Trigger after OAuth access token has been refreshed  |

## JWT

[JSON Web Token RFC (JWT)](https://datatracker.ietf.org/doc/html/rfc7519).

All the tokens exposed follow the OpenId Connect Token and Access Tokens [Specs](https://openid.net/specs/openid-connect-core-1_0.html#IDToken).

This modules manages the following tokens:

| JWT Name                  | Status         | Description                                                                                                                     |
| ------------------------- | -------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| Shop Token (legacy)       | Deprecated 7.x | This token can be used to act as the shop. It should be used only for machine to machine communication without user interaction |
| Shop Owner Token (legacy) | Deprecated 7.X | This token is created for the owner who associate the shop.                                                                     |
| Authenticated User Token  | Introduced 6.x | ex: Backend Login with PrestaShop SSO                                                                                           |
| OAuth Shop Access Token   | Introduced 7.X | For machine to machine calls. (also used to keep up to date legacy Shop and Owner tokens).                                      |

## Development

This module has three parts:

- [PS Accounts module](http://github.com/PrestaShopCorp/ps_accounts)
  - This module must be installed.
  - It's your interface between your module and PrestaShop Accounts service.
- [PS Accounts Installer (Composer Library)](http://github.com/PrestaShopCorp/prestashop-accounts-installer)
  - This library's role is here to compensate a lack of security between modules dependencies. If PS Accounts is removed while your module is still installed: it causes a crash of the PrestaShop module's page/feature.
  - This library is here to install automatically PS Accounts if it's missing.
  - It's your interface between your module and PrestaShop Accounts module
  - You should never require directly PrestaShop\Module\PsAccounts namespace classes
- [PrestaShop Accounts Vue Components](http://github.com/PrestaShopCorp/prestashop_accounts_vue_components)
  - It's the front-end component you need to integrate into your module's configuration page.

## How to start working with PS Accounts as a PSx or Community Service developer?

- [Read the official documentation here](https://devdocs.prestashop-project.org/8/modules/)
- Clone this repository

### Testing

This repository has a Makefile. Just run for running phpunit `make phpunit` and `make phpstan`.

### JWT

We use JWTs for 2 types of account: the user account and the shop account.
What we're identifying when we link a PrestaShop shop is **a shop**. A shop belongs to 1 owner (user).

There are 2 Firebase projects:

- **prestashop-newsso-production** is the Firebase Authentication project we're using to authenticate **users** _(prestashop-newsso-staging) for staging environment_
- **prestashop-ready-prod** is the Firebase Authentication project we're using to authenticate **shops** _(psessentials-integration) for integration environment_

### How to get upd to date (legacy) JWT Tokens

```php
use PrestaShop\PsAccountsInstaller\Installer\Installer;
use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;

define('MIN_PS_ACCOUNTS_VERSION', '4.0.0');

$facade = new PsAccounts(new Installer(MIN_PS_ACCOUNTS_VERSION));

// Get or refresh shop token
$shopToken = $facade->getPsAccountsService()->getOrRefreshToken();

// Get or refresh shop owner token
$ownerToken = $facade->getPsAccountsService()->getUserToken();
```

see: [PrestaShop Accounts Installer](http://github.com/PrestaShopCorp/prestashop-accounts-installer) for more details on how to setup Installer.

## Breaking Changes

### Removal of the environment variables

This module don't use a .env file as a configuration file. We are using YAML files with a Symfony service container to configure services and their injected dependencies as well as configuration parameters.

### Composer dependency `prestashop_accounts_auth` deprecated

This library will be deprecated and no longer needed.
Please remove it from your module's dependencies.

### New composer dependency `prestashop-accounts-installer`

**Do not directly import PrestaShop Accounts classes**

If you need to call PrestaShop Accounts public classes's methods, you need to use the service container.

see: [PrestaShop Accounts Installer](http://github.com/PrestaShopCorp/prestashop-accounts-installer)

### PS EventBus is no longer installed for 1.6.x versions

The ps_eventbus module is no longer installed automatically for Prestashop version <1.7.

### APIs removal

Those API has been removed:

- `/carts`
- `/categories`
- `/deletedObjects`
- `/googleTaxonomies`
- `/apiHealthCheck`
- `/info`
- `/modules`
- `/orders`
- `/products`
- `/themes`
