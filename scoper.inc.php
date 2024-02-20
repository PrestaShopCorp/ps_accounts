<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

#ini_set('memory_limit', '1024M');

use Isolated\Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

// Example of collecting files to include in the scoped build but to not scope
// leveraging the isolated finder.
//$excludedFiles = array_map(
//    static fn (SplFileInfo $fileInfo) => $fileInfo->getPathName(),
//    iterator_to_array(
//        Finder::create()->files()->in(__DIR__),
//        false,
//    ),
//);

return [
    // The prefix configuration. If a non-null value is used, a random prefix
    // will be generated instead.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
    'prefix' => null,

    // The base output directory for the prefixed files.
    // This will be overridden by the 'output-dir' command line option if present.
    'output-dir' => '',

    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // This configuration entry is completely ignored when using Box.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
    'finders' => [
        Finder::create()->files()->in('config'),
        Finder::create()->files()->in('controllers'),
        Finder::create()->files()->in('scripts'),
        Finder::create()->files()->in('sql'),
        Finder::create()->files()->in('src'),
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock|Dockerfile/')
            ->exclude([
                'doc',
                'test',
                'test_old',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('vendor'),
        Finder::create()->files()->in('translations'),
        Finder::create()->files()->in('upgrade'),
        Finder::create()->files()->in('views'),
        Finder::create()->append([
            'CHANGELOG.md',
            'composer.json',
            'composer.lock',
            'composer.phar',
            'config.xml',
            'index.php',
            'LICENSE',
            'logo.png',
            'ps_accounts.php',
        ]),
    ],

    // List of excluded files, i.e. files for which the content will be left untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'exclude-files' => [
        // 'src/an-excluded-file.php',
        //...$excludedFiles,
        //'ps_accounts.php'
    ],

    // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
    // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
    // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
    // heart contents.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'patchers' => [
        /*static function (string $filePath, string $prefix, string $contents): string {
            // Change the contents here.
            return $contents;
        },*/
    ],

    // List of symbols to consider internal i.e. to leave untouched.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
    'exclude-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        //'~^$~',                        // The root namespace only
        // '',
        '~^PrestaShop\\\\Module\\\\PsAccounts~',
        '~^PrestaShop\\\\PrestaShop~',
        '~^PrestaShopBundle~',
        // FIXME
        '~^Symfony~',
    ],
    'exclude-classes' => [
        // 'ReflectionClassConstant',
        //'\Ps_accounts',
        '\Module',
        '\Db',
        '\Tab',
        '\Language',
        '\Tools',
        '\Hook',
        '\Configuration',
        '\Context',
        '\Link',
        '\PrestaShopException',
        '\Media',
        '\Shop',
        '\Employee',
        '\ModuleAdminController',
        '\AdminDebugPsAccountsController',
        '\AdminAjaxPsAccountsController',
        '\AdminLoginPsAccountsController',
        '\AdminOAuth2PsAccountsController',
    ],
    'exclude-functions' => [
        // 'mb_str_split',
        //'array_merge'
    ],
    'exclude-constants' => [
        // 'STDIN',
    ],

    // List of symbols to expose.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
    'expose-global-constants' => true,
    'expose-global-classes' => true,
    'expose-global-functions' => true,
    'expose-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        // '~^$~',                        // The root namespace only
        // '',                            // Any namespace
        //'~^PrestaShop\\\\Module\\\\PsAccounts~'
    ],
    'expose-classes' => [],
    'expose-functions' => [],
    'expose-constants' => [],
];
