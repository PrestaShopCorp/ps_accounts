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

namespace PrestaShop\Module\PsAccounts\Api\Client;

use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\AbstractGuzzleClient;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Repository\TokenClientInterface;

/**
 * Class ServicesAccountsClient
 */
class SsoClient implements TokenClientInterface
{
    /**
     * @var AbstractGuzzleClient
     */
    private $client;

    /**
     * ServicesAccountsClient constructor.
     *
     * @param string $apiUrl
     * @param AbstractGuzzleClient|null $client
     */
    public function __construct(
        $apiUrl,
        AbstractGuzzleClient $client = null
    ) {
        if (null === $client) {
            $client = (new GuzzleClientFactory())->create([
                'base_url' => $apiUrl,
                'defaults' => [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            ]);
        }

        $this->client = $client;
    }

    /**
     * @param string $idToken
     *
     * @return array response
     */
    public function verifyToken($idToken)
    {
        $this->client->setRoute('auth/token/verify');

        return $this->client->post([
            'json' => [
                'token' => $idToken,
            ],
        ]);
    }

    /**
     * @param string $refreshToken
     *
     * @return array response
     */
    public function refreshToken($refreshToken)
    {
        $this->client->setRoute('auth/token/refresh');

        return $this->client->post([
            'json' => [
                'token' => $refreshToken,
            ],
        ]);
    }
}
