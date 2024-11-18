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

use PrestaShop\Module\PsAccounts\Vendor\GuzzleHttp\Client;

/**
 * Interface that the guzzle client class implement
 */
interface ClientInterface
{
    /**
     * Abtract client constructor
     *
     * @param array $options
     */
    public function __construct($options);

    /**
     * @return Client
     */
    public function getClient();

    /**
     * @param mixed $response
     *
     * @return array
     */
    public function handleResponse($response);

    /**
     * Check if the response is successful or not (response code 200 to 299).
     *
     * @param array $responseContents
     * @param int $httpStatusCode
     *
     * @return bool
     */
    public function responseIsSuccessful($responseContents, $httpStatusCode);

    /**
     * @param mixed $response
     *
     * @return mixed
     */
    public function getResponseJson($response);
}
