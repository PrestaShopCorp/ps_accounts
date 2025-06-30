# PrestaShop Account module

[![Source Code](https://img.shields.io/badge/source-PrestaShopCorp/ps_accounts-blue.svg?style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts)
[![Latest Version](https://img.shields.io/github/release/PrestaShopCorp/ps_accounts.svg?style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts/releases)
[![Software License](https://img.shields.io/badge/license-OSL-brightgreen.svg?style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts/blob/main/LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/PrestaShopCorp/ps_accounts/.github/workflows/accounts-qc-php.yml?label=CI&logo=github&style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts/actions?query=workflow%3ACI)

# Context

The module **ps_accounts** is the interface between your module and PrestaShop's services. It manages:
- Shop association/dissociation process;
- Providing tokens to communicate safely with PrestaShop services;
- Synchronize basic informations about the shops (ex: shop URLs, name, ...).

This module is a basis for other modules using PrestaShop services.

# Installation

If you need to install and test the module, [you can download the desired zip here](https://github.com/PrestaShopCorp/ps_accounts/releases).

## Compatibility Matrix

We aims to follow partially the Prestashop compatibility charts
- [Compatibility Chart Prestashop 1.6 & 1.7](https://devdocs.prestashop.com/1.7/basics/installation/system-requirements/#php-compatibility-chart)
- [Compatibility Chart Prestashop 8.0](https://devdocs.prestashop.com/8/basics/installation/system-requirements/#php-compatibility-chart)
- [Compatibility Chart Prestashop 9.0](https://devdocs.prestashop.com/9/basics/installation/system-requirements/#php-compatibility-chart)

| ps_accounts version  | Prestashop Version   | PHP Version       |
|----------------------|----------------------|-------------------|
| ^7.0.9               | \>=1.6 && <= 9.x     | PHP 5.6 - 8       |
| 7.x                  | \>=1.6 && <9.x       | PHP 5.6 - 8       |
| ~~6.x (deprecated)~~ | ~~\>=8.0.0~~         | ~~PHP 7.2 - 8~~   |
| ~~5.x (deprecated)~~ | ~~\>=1.6 && <8.0.0~~ | ~~PHP 5.6 - 7.4~~ |

# Integration along with your Prestashop module

If you are integrating a module, you should have a look on the [PrestaShop Integration Framework Documentation](https://docs.cloud.prestashop.com/).

## A preliminary note about PrestaShop modules ecosystem :

### You should keep in mind that the PrestaShop Core
- **_doesn't_** manage dependencies between modules;
- **_doesn't_** manage composer dependencies globally.

### As a consequence you MUST
- check by yourself that the PsAccounts module is installed;
- ensure your vendor dependencies won't collide with existing ones.  
  (loaded by other modules OR coming from the PrestaShop Core)

## Display the PrestaShop Account Component in your module :

### Load PsAccountsPresenter

The presenter will give basic informations to the components through `contextPsAccounts` object accessible on the page.

```php
// My_module.php

// /!\ TODO: Starting here you are responsible to check that the module is installed

/** @var Ps_accounts $module */
$module = \Module::getModuleIdByName('ps_accounts');

/** @var \PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter $presenter */
$presenter = $module->getService(\PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter::class);

Media::addJsDef([
    'contextPsAccounts' => $presenter->present((string) $this->name),
]);

return $this->display(__FILE__, 'views/templates/admin/app.tpl');
```
Alternatively you can still use : [PrestaShop Accounts Installer](http://github.com/PrestaShopCorp/prestashop-accounts-installer) for more details on how to setup Installer.

### Load and init the component on your page

For detailed usage you can follow the component's documentation : [prestashop_accounts_vue_components](https://github.com/PrestaShopCorp/prestashop_accounts_vue_components)

## How to retrieve tokens with PsAccounts

### About tokens provided :

All the [JWT](https://datatracker.ietf.org/doc/html/rfc7519) tokens exposed follow the OpenId Connect Token and Access Tokens [Specs](https://openid.net/specs/openid-connect-core-1_0.html#IDToken).

This module provides the following tokens:

- **ShopToken** _(legacy Firebase tokens)_  
  This token can be used to act as the shop. It should be used only for machine to machine communication without user interaction
- **OwnerToken** _(legacy Firebase tokens)_  
  This token is created for the shop owner who associate the shop.
- **ShopAccessToken** (provided by [Prestashop OpenId Connect Provider](https://oauth.prestashop.com/.well-known/openid-configuration))  
  For machine to machine calls. (also used to keep up to date legacy Shop and Owner tokens

### How to get shop status

#### Retrieving v8 Shop Status
```php
// /!\ TODO: Starting here you are responsible to check that the module is installed

/** @var Ps_accounts $module */
$module = \Module::getModuleIdByName('ps_accounts');

/** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $service */
$service = $module->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);

// Starting from v8 status has been split into 3 distinct information
$service->isShopIdentityCreated();
$service->isShopIdentityVerified();
$service->isShopPointOfContactSet();
```

#### Shop status compatibility with v7
```php

// strictly equivalent to:
//      service->isShopIdentityCreated() &&
//      $service->isShopIdentityVerified() &&
//      $service->isShopPointOfContactSet()
$isShopLinked = $service->isAccountLinked();
```

#### Tokens legacy compatibility table

|                         | PrestaShop AccessToken | Scopes        | Legacy<br/>Firebase Shop Id Token | Legacy<br/>Firebase User Id Token |
|-------------------------|------------------------|---------------|-----------------------------------|-----------------------------------|
| isShopIdentityCreated   | Yes                    |               | Yes                               | No                                |
| isShopIdentityVerified  | Yes                    | shop.verified | Yes                               | No                                |
| isShopPointOfContactSet | Yes                    | shop.verified | Yes                               | Yes                               |
| isAccountLinked         | Yes                    | shop.verified | Yes                               | Yes                               |


### How to get up-to-date JWT Shop Access Tokens

```php
// /!\ TODO: Starting here you are responsible to check that the module is installed

/** @var Ps_accounts $module */
$module = \Module::getModuleIdByName('ps_accounts');

/** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $service */
$service = $module->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);

try {
    $jwtAccessToken = $service->getShopToken();
} catch (\PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException $e) {
    // 
}
```

### How to get up-to-date (legacy) JWT Tokens
```php
// /!\ TODO: Starting here you are responsible to check that the module is installed

/** @var Ps_accounts $module */
$module = \Module::getModuleIdByName('ps_accounts');

/** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $service */
$service = $module->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);

// With this one you can call your API's as the PrestaShop Account Shop
$jwtShop = $service->getOrRefreshToken();

// With this one you can call your API's as the PrestaShop Account Shop Owner
$jwtOwner = $service->getUserToken();
```

[//]: # (OR :)

[//]: # ()
[//]: # (```php)

[//]: # (use PrestaShop\PsAccountsInstaller\Installer\Installer;)

[//]: # (use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;)

[//]: # ()
[//]: # (define&#40;'MIN_PS_ACCOUNTS_VERSION', '7.0.0'&#41;;)

[//]: # ()
[//]: # ($facade = new PsAccounts&#40;new Installer&#40;MIN_PS_ACCOUNTS_VERSION&#41;&#41;;)

[//]: # ()
[//]: # (// Get or refresh shop token)

[//]: # ($shopToken = $facade->getPsAccountsService&#40;&#41;->getOrRefreshToken&#40;&#41;;)

[//]: # ()
[//]: # (// Get or refresh shop owner token )

[//]: # ($ownerToken = $facade->getPsAccountsService&#40;&#41;->getUserToken&#40;&#41;;)

[//]: # (```)

### Calling AJAX controller in backend context (legacy shop token only)
That way you will retrieve an up to date **Shop Token**
```js
const response = await fetch("https://<shop-admin-url>/index.php", {
  ajax: true,
  controller: 'AdminAjaxPsAccounts',
  action: 'getOrRefreshAccessToken',
  token: '<admin_token>',
});
```
With the given response :
```json
{ 
  "token": "<access_token>"
}
```

## Provided hooks

Here are listed custom hooks provided with this module:

| hook                              | params           | description                                  |
|-----------------------------------|------------------|----------------------------------------------|
| actionShopAccountLinkAfter        | shopId, shopUuid | Triggered after link shop acknowledged       |
| actionShopAccountUnlinkAfter      | shopId, shopUuid | Triggered after unlink shop acknowledged     |
| actionShopAccessTokenRefreshAfter | token            | Triggered after OAuth access token refreshed |

# Building the module locally

In case you need to build a zip by yourself :

```shell
  cp config.dist.php config.php
  make
```

OR with multiple environments :

```shell
  cp config.dist.php config.myenv.php
  BUNDLE_ENV=myenv make
```

# Deprecation Notices [WIP]

### v7.2.0

* PrestaShop\Module\PsAccounts\Api\Client\ServicesBillingClient
* PrestaShop\Module\PsAccounts\Exception\RefreshTokenException
* PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession

