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

class Response
{
    /**
     * @var array
     */
    protected $body;

    /**
     * @var mixed
     */
    protected $raw;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var bool
     */
    protected $isValid;

    /**
     * @param array|string $body
     * @param int $statusCode
     */
    public function __construct($body, $statusCode)
    {
        $this->raw = $body;
        $this->body = $this->decodeBody($body);
        $this->statusCode = (int) $statusCode;
        $this->isValid = '2' === substr((string) $statusCode, 0, 1);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return mixed
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * @return array
     */
    public function toLegacy()
    {
        return [
            'status' => $this->isValid,
            'httpCode' => $this->statusCode,
            'body' => $this->body,
        ];
    }

    /**
     * @param array|string $body
     *
     * @return array
     */
    public function decodeBody($body)
    {
        if (is_array($body)) {
            return $body;
        } else {
            $decodedBody = json_decode($body, true);

            return is_array($decodedBody) ? $decodedBody : [];
        }
    }
}
