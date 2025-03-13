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
 * @property mixed $handler
 * @property string $uri
 * @property string $absUri
 * @property string|null $method
 * @property array $headers
 * @property array|null $json
 * @property array|null $form
 * @property array|null $query
 */
class Request extends ConfigObject
{
    const HANDLER = 'handler';
    const URI = 'uri';
    const ABS_URI = 'absUri';
    const METHOD = 'method';
    const HEADERS = 'headers';
    const JSON = 'json';
    const FORM = 'form';
    const QUERY = 'query';

    protected $defaults = [
        self::HANDLER => null,
        self::URI => null,
        self::ABS_URI => null,
        self::METHOD => null,
        self::HEADERS => [],
        self::JSON => null,
        self::FORM => null,
        self::QUERY => null,
    ];
}
