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

use PrestaShop\Module\PsAccounts\Type\ConfigObject;

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
    const name = 'name';
    const baseUri = 'baseUri';
    const userAgent = 'userAgent';
    const timeout = 'timeout';
    const sslCheck = 'sslCheck';
    const allowRedirects = 'allowRedirects';
    const headers = 'headers';

    protected $defaults = [
        self::name => '',
        self::userAgent => '',
        self::timeout => 10,
        self::sslCheck => true,
        self::headers => [],
        self::allowRedirects => false,
    ];

    protected $required = [
        self::baseUri,
    ];
}
