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

namespace PrestaShop\Module\PsAccounts\Http\Client;

/**
 * @property string $name
 * @property string $baseUri
 * @property string $userAgent
 * @property int $timeout
 * @property bool $sslCheck
 * @property bool $allowRedirects
 * @property array $headers
 */
class ClientConfig extends ConfigObject
{
    const NAME = 'name';
    const BASE_URI = 'baseUri';
    const USER_AGENT = 'userAgent';
    const TIMEOUT = 'timeout';
    const SSL_CHECK = 'sslCheck';
    const ALLOW_REDIRECTS = 'allowRedirects';
    const HEADERS = 'headers';

    protected $defaults = [
        self::NAME => '',
        self::USER_AGENT => '',
        self::TIMEOUT => 10,
        self::SSL_CHECK => true,
        self::HEADERS => [],
        self::ALLOW_REDIRECTS => false,
    ];

    protected $required = [
        self::BASE_URI,
    ];
}
