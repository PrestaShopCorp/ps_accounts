# PrestaShop Account module

[![Source Code](https://img.shields.io/badge/source-PrestaShopCorp/ps_accounts-blue.svg?style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts)
[![Latest Version](https://img.shields.io/github/release/PrestaShopCorp/ps_accounts.svg?style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts/releases)
[![Software License](https://img.shields.io/badge/license-OSL-brightgreen.svg?style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts/blob/main/LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/PrestaShopCorp/ps_accounts/.github/workflows/accounts-qc-php.yml?label=CI&logo=github&style=flat-square)](https://github.com/PrestaShopCorp/ps_accounts/actions?query=workflow%3ACI)

# Context

The module **ps_accounts** is the interface between your module and PrestaShop's services. It manages:
- Shop **Identification** and **Verification** process;
- Providing tokens to communicate safely with PrestaShop services;
- Synchronize basic informations about the shops (ex: shop URLs, name, ...).

This module is a base component for other modules using PrestaShop services.

# Installation

If you need to install and test the module, [you can download the desired zip here](https://github.com/PrestaShopCorp/ps_accounts/releases).

## Compatibility Matrix

We aims to follow partially the Prestashop compatibility charts
- [Compatibility Chart Prestashop 1.6 & 1.7](https://devdocs.prestashop.com/1.7/basics/installation/system-requirements/#php-compatibility-chart)
- [Compatibility Chart Prestashop 8.0](https://devdocs.prestashop.com/8/basics/installation/system-requirements/#php-compatibility-chart)
- [Compatibility Chart Prestashop 9.0](https://devdocs.prestashop.com/9/basics/installation/system-requirements/#php-compatibility-chart)

| ps_accounts version  | Prestashop Version   | PHP Version       |
|----------------------|----------------------|-------------------|
| ^8.0.0               | \>=1.6 && <= 9.x     | PHP 5.6 - 8       |
| ^7.0.9               | \>=1.6 && <= 9.x     | PHP 5.6 - 8       |
| 7.x                  | \>=1.6 && <9.x       | PHP 5.6 - 8       |
| ~~6.x (deprecated)~~ | ~~\>=8.0.0~~         | ~~PHP 7.2 - 8~~   |
| ~~5.x (deprecated)~~ | ~~\>=1.6 && <8.0.0~~ | ~~PHP 5.6 - 7.4~~ |

# Integration along with your Prestashop module

If you are integrating a module, you should have a look on the [PrestaShop Integration Framework Documentation](https://docs.cloud.prestashop.com/9-prestashop-integration-framework/0-introduction/).

## A preliminary note about PrestaShop modules ecosystem :

### You should keep in mind that the PrestaShop Core
- **_doesn't_** manage dependencies between modules;
- **_doesn't_** manage composer dependencies globally.

### As a consequence you MUST
- check by yourself that the PsAccounts module is installed;
- ensure your vendor dependencies won't collide with existing ones.  
  (loaded by other modules OR coming from the PrestaShop Core)


#### Tokens availability with legacy compatibility table

| Method Name             | PrestaShop AccessToken | Scopes        | **_Legacy_**<br/>Firebase Shop Id Token | **_Legacy_**<br/>Firebase User Id Token |
|-------------------------|------------------------|---------------|-----------------------------------------|-----------------------------------------|
| **_>= v8.0.0_**         |                        |               |                                         |                                         |
| isShopIdentityCreated   | Yes                    |               | Yes                                     | No                                      |
| isShopIdentityVerified  | Yes                    | shop.verified | Yes                                     | No                                      |
| isShopPointOfContactSet | Yes                    | shop.verified | Yes                                     | Yes                                     |
| **_< v8.0.0_**          |                        |               |                                         |                                         |
| isAccountLinked         | Yes                    | shop.verified | Yes                                     | Yes                                     |


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

