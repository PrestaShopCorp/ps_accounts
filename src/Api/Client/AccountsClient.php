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

use PrestaShop\Module\PsAccounts\Account\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Factory\CircuitBreakerFactory;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\CircuitBreaker;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClient;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;

/**
 * Class ServicesAccountsClient
 */
class AccountsClient implements TokenClientInterface
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var CircuitBreaker
     */
    private $circuitBreaker;

    /**
     * @var int
     */
    private $defaultTimeout;

    /**
     * ServicesAccountsClient constructor.
     *
     * @param string $apiUrl
     * @param ShopProvider $shopProvider
     * @param GuzzleClient|null $client
     * @param int $defaultTimeout
     *
     * @throws \Exception
     */
    public function __construct(
        $apiUrl,
        ShopProvider $shopProvider,
        GuzzleClient $client = null,
        $defaultTimeout = 20
    ) {
        $this->apiUrl = $apiUrl;
        $this->shopProvider = $shopProvider;
        $this->client = $client;
        $this->circuitBreaker = CircuitBreakerFactory::create('ACCOUNTS_CLIENT');
        $this->defaultTimeout = $defaultTimeout;
    }

    /**
     * @return GuzzleClient
     */
    private function getClient()
    {
        if (null === $this->client) {
            $this->client = (new GuzzleClientFactory())->create([
                'base_uri' => $this->apiUrl,
                'headers' => $this->getHeaders(),
                'timeout' => $this->defaultTimeout,
            ]);
        }

        return $this->client;
    }

    /**
     * @param string $idToken
     *
     * @return array response
     */
    public function verifyToken($idToken)
    {
        $this->getClient()->setRoute('shop/token/verify');

        return $this->getClient()->post([
            'headers' => $this->getHeaders([
                'X-Shop-Id' => $this->getShopUuid(),
            ]),
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
        return $this->circuitBreaker->call(function () use ($refreshToken) {
            $this->getClient()->setRoute('shop/token/refresh');

            return $this->getClient()->post([
                'headers' => $this->getHeaders([
                    'X-Shop-Id' => $this->getShopUuid(),
                ]),
                'json' => [
                    'token' => $refreshToken,
                ],
            ]);
        });
    }

    /**
     * @param string $ownerUid
     * @param string $shopUid
     * @param string $ownerToken
     *
     * @return array
     */
    public function deleteUserShop($ownerUid, $shopUid, $ownerToken)
    {
        $this->client->setRoute('user/' . $ownerUid . '/shop/' . $shopUid);

        return $this->getClient()->delete([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
                'X-Shop-Id' => $this->getShopUuid(),
            ]),
        ]);
    }

    /**
     * @param string $shopUid
     * @param string $shopToken
     * @param array $payload
     *
     * @return array
     */
    public function reonboardShop($shopUid, $shopToken, $payload)
    {
        $this->getClient()->setRoute('shop/' . $shopUid . '/reonboard');

        return $this->getClient()->post([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $shopToken,
                'X-Shop-Id' => $shopUid,
            ]),
            'json' => $payload,
        ]);
    }

    /**
     * @param string $ownerUid
     * @param string $shopUid
     * @param string $ownerToken
     * @param UpdateShop $shop
     *
     * @return array
     */
    public function updateUserShop($ownerUid, $shopUid, $ownerToken, UpdateShop $shop)
    {
        $this->getClient()->setRoute('user/' . $ownerUid . '/shop/' . $shopUid);

        return $this->getClient()->patch([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
                'X-Shop-Id' => $shopUid,
            ]),
            'json' => $shop->jsonSerialize(),
        ]);
    }

    /**
     * @param array $additionalHeaders
     *
     * @return array
     */
    private function getHeaders($additionalHeaders = [])
    {
        return array_merge([
            'Accept' => 'application/json',
            'X-Module-Version' => \Ps_accounts::VERSION,
            'X-Prestashop-Version' => _PS_VERSION_,
        ], $additionalHeaders);
    }

    /**
     * @return CircuitBreaker
     */
    public function getCircuitBreaker()
    {
        return $this->circuitBreaker;
    }

    /**
     * @return string
     */
    private function getShopUuid()
    {
        return $this->shopProvider->getShopContext()->getConfiguration()->getShopUuid();
    }
}
