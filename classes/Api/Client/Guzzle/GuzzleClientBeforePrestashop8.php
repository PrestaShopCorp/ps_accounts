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

namespace PrestaShop\Module\PsAccounts\Api\Client\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\AbstractGuzzleClient;

/**
 * Construct the guzzle client before PrestaShop 8
 */
class GuzzleClientBeforePrestashop8 extends AbstractGuzzleClient
{
    /**
     * Constructor for client before PrestaShop 8
     */
    public function __construct($options)
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        if (! isset($options['defaults']['timeout'])) {
            $options['defaults']['timeout'] = $this->timeout;
        }

        if (! isset($options['defaults']['exceptions'])) {
            $options['defaults']['exceptions'] = $this->catchExceptions;
        }

        $client = new Client($options);

        $client->setDefaultOption(
            'verify',
            (bool) $module->getParameter('ps_accounts.check_api_ssl_cert')
        );

        $this->client = $client;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array
     */
    public function handleResponse($response)
    {
        $responseContents = json_decode($response->getBody()->getContents(), true);

        return [
            'status' => $this->responseIsSuccessful($responseContents, $response->getStatusCode()),
            'httpCode' => $response->getStatusCode(),
            'body' => $responseContents,
        ];
    }
}
