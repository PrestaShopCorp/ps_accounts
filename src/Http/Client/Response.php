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
 * @property array $body
 * @property mixed $raw
 * @property int $statusCode
 * @property bool $isSuccessful
 */
class Response extends ConfigObject
{
    const body = 'body';
    const raw = 'raw';
    const statusCode = 'statusCode';
    const isSuccessful = 'isSuccessful';

    /**
     * @param array|string $body
     * @param int $statusCode
     */
    public function __construct($body, $statusCode)
    {
        parent::__construct([
            self::raw => $body,
            self::body => $this->decodeBody($body),
            self::statusCode => (int) $statusCode,
            self::isSuccessful => '2' === substr((string) $statusCode, 0, 1),
        ]);
    }

    /**
     * @return array
     */
    public function toLegacy()
    {
        return [
            'status' => $this->isSuccessful,
            'httpCode' => $this->statusCode,
            'body' => $this->body,
        ];
    }

    /**
     * @param array|string $body
     *
     * @return array
     */
    protected function decodeBody($body)
    {
        if (is_array($body)) {
            return $body;
        } else {
            $decodedBody = json_decode($body, true);

            return is_array($decodedBody) ? $decodedBody : [];
        }
    }
}
