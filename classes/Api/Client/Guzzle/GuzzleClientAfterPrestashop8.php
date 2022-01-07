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
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\AbstractGuzzleClient;

/**
 * Construct the client with the new guzzle version of PrestaShop 8
 */
class GuzzleClientAfterPrestashop8 extends AbstractGuzzleClient
{
    /**
     * Constructor for client after PrestaShop 8
     */
    public function __construct($options)
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        $payload = [];

        if (isset($options['defaults']['headers'])) {
            $payload['headers'] = $options['defaults']['headers'];
        }

        if (isset($options['defaults']['timeout'])) {
            $payload['timeout'] = $options['defaults']['timeout'];
        }

        if (isset($options['defaults']['exceptions'])) {
            $payload['http_errors'] = $options['defaults']['exceptions'];
        }

        $this->client = new Client(
            array_merge(
                [
                    'base_uri' => $options['base_url'],
                    'verify' => (bool) $module->getParameter('ps_accounts.check_api_ssl_cert'),
                    'timeout' => $this->timeout,
                    'http_errors' => $this->catchExceptions
                ],
                $payload
            )
        );
    }

    // FIXME Lots of phpstan error because it doesn't exist in current guzzle package

    /**
     * @phpstan-ignore-next-line
     *
     * @param \GuzzleHttp\Psr7\Response $response
     *
     * @return array
     */
    public function handleResponse($response)
    {
        /* @phpstan-ignore-next-line */
        $responseContents = json_decode($response->getBody()->getContents(), true);

        /* @phpstan-ignore-next-line */
        return [
            'status' => $this->responseIsSuccessful($responseContents, $response->getStatusCode()), //@phpstan-ignore-line
            'httpCode' => $response->getStatusCode(), //@phpstan-ignore-line
            'body' => $responseContents,
        ];
    }
}
