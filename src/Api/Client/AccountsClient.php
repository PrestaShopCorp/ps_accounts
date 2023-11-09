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

use PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker\CircuitBreaker;
use PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker\CircuitBreakerFactory;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\AbstractGuzzleClient;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\DTO\UpdateShop;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\TokenClientInterface;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

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
     * @var AbstractGuzzleClient
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
     * @param AbstractGuzzleClient|null $client
     * @param int $defaultTimeout
     */
    public function __construct(
        string $apiUrl,
        ShopProvider $shopProvider,
        AbstractGuzzleClient $client = null,
        int $defaultTimeout = 20
    ) {
        $this->apiUrl = $apiUrl;
        $this->shopProvider = $shopProvider;
        $this->client = $client;
        $this->circuitBreaker = CircuitBreakerFactory::create('ACCOUNTS_CLIENT');
        $this->defaultTimeout = $defaultTimeout;
    }

    /**
     * @return AbstractGuzzleClient
     */
    private function getClient()
    {
        if (null === $this->client) {
            $this->client = (new GuzzleClientFactory())->create([
                'base_url' => $this->apiUrl,
                'defaults' => [
                    'headers' => $this->getHeaders(),
                    'timeout' => $this->defaultTimeout,
                ],
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
                'X-Shop-Id' => $this->shopProvider->getShopContext()->getConfiguration()->getShopUuid(),
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
                    'X-Shop-Id' => $this->shopProvider->getShopContext()->getConfiguration()->getShopUuid(),
                ]),
                'json' => [
                    'token' => $refreshToken,
                ],
            ]);
        });
    }

    /**
     * @param int $shopId
     *
     * @return array
     *
     * @throws \Exception
     */
    public function deleteUserShop($shopId)
    {
        return $this->shopProvider->getShopContext()->execInShopContext((int) $shopId, function () {
            $userToken = $this->getUserTokenRepository();
            $shopToken = $this->getShopTokenRepository();

            $this->getClient()->setRoute('user/' . $userToken->getTokenUuid() . '/shop/' . $shopToken->getTokenUuid());

            return $this->getClient()->delete([
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $userToken->getOrRefreshToken(),
                    'X-Shop-Id' => $shopToken->getTokenUuid(),
                ]),
            ]);
        });
    }

    /**
     * @param array $currentShop
     *
     * @return array
     *
     * @throws \Exception
     */
    public function reonboardShop($currentShop)
    {
        return $this->shopProvider->getShopContext()->execInShopContext((int) $currentShop['id'], function () use ($currentShop) {
            $shopToken = $this->getShopTokenRepository();

            $this->getClient()->setRoute('shop/' . $currentShop['uuid'] . '/reonboard');

            return $this->getClient()->post([
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken->getOrRefreshToken(),
                    'X-Shop-Id' => $currentShop['uuid'],
                ]),
                'json' => $currentShop,
            ]);
        });
    }

    /**
     * @param UpdateShop $shop
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function updateUserShop(UpdateShop $shop)
    {
        return $this->shopProvider->getShopContext()->execInShopContext((int) $shop->shopId, function () use ($shop) {
            $userToken = $this->getUserTokenRepository();
            $shopToken = $this->getShopTokenRepository();

            /** @var \Ps_accounts $module */
            $module = \Module::getInstanceByName('ps_accounts');

            /** @var ShopLinkAccountService $linkAccountService */
            $linkAccountService = $module->getService(ShopLinkAccountService::class);

            if (!$linkAccountService->isAccountLinked()) {
                return null;
            }

            $this->getClient()->setRoute('user/' . $userToken->getTokenUuid() . '/shop/' . $shopToken->getTokenUuid());

            return $this->getClient()->patch([
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $userToken->getOrRefreshToken(),
                    'X-Shop-Id' => $shopToken->getTokenUuid(),
                ]),
                'json' => $shop->jsonSerialize(),
            ]);
        });
    }

    /**
     * @return CircuitBreaker
     */
    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
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
     * @return ShopTokenRepository
     *
     * @throws \Exception
     */
    private function getShopTokenRepository()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        return $module->getService(ShopTokenRepository::class);
    }

    /**
     * @return UserTokenRepository
     *
     * @throws \Exception
     */
    private function getUserTokenRepository()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        return $module->getService(UserTokenRepository::class);
    }
}
