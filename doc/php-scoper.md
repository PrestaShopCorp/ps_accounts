# Using php-scoper

## Intro, scoping

We decide to scope only a few troublesome dependencies here to avoid version conflicts in mixed up PrestaShop module's context.

## Setup & run php-scoper

Vendor directories to scope are primarily defined in `scoper.inc.php`

```php
// Vendor dependency dirs your want to scope
// Note: you'll have to manually add namespaces in your composer.json
$dirScoped = ['guzzlehttp', 'league', 'prestashopcorp', 'lcobucci'];
```

You'll still have to manually define namespaces in `composer.json` :

```json
"autoload": {
    "psr-4": {
        "PrestaShop\\Module\\PsAccounts\\": "src/",
        "PrestaShop\\Module\\PsAccounts\\Vendor\\GuzzleHttp\\": "vendor/guzzlehttp/guzzle/src",
        "PrestaShop\\Module\\PsAccounts\\Vendor\\GuzzleHttp\\Promise\\": "vendor/guzzlehttp/promises/src",
        "PrestaShop\\Module\\PsAccounts\\Vendor\\GuzzleHttp\\Psr7\\": "vendor/guzzlehttp/psr7/src",
        "PrestaShop\\Module\\PsAccounts\\Vendor\\League\\OAuth2\\Client\\": "vendor/league/oauth2-client/src",
        "PrestaShop\\Module\\PsAccounts\\Vendor\\Lcobucci\\JWT\\": "vendor/lcobucci/jwt/src"
    },
},
```

NOTE: you also have to use prefixed namespaces in the module's code. We only scope vendors dirs here.

For in-string namespaces references ("indirect" references), we need to use Patchers :
see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers

The `fix-autoload.php` script will fix remaining function libraries autoload problem :
see: https://github.com/humbug/php-scoper/issues/298

To scope in place module run : 

```shell
make php-scoper
``` 

Scope & bundle module:

```shell
COMPOSER_OPTIONS="--no-dev -o" make
```
