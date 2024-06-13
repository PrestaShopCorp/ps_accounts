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

<<<<<<< HEAD
use PrestaShop\Module\PsAccounts\Account\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Account\Dto\UpgradeModule;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClient;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClientFactory;
=======
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\AbstractGuzzleClient;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\TokenClientInterface;
use PrestaShop\Module\PsAccounts\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

class AccountsClient
{
    /**
     * @var string
     */
    private $apiUrl;
<<<<<<< HEAD
=======

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

    /**
     * @var GuzzleClient
     */
    private $client;

<<<<<<< HEAD
    /**
     * @var int
     */
    private $defaultTimeout;

    /**
     * ServicesAccountsClient constructor.
     *
     * @param string $apiUrl
     * @param GuzzleClient|null $client
     * @param int $defaultTimeout
     *
     * @throws \Exception
     */
    public function __construct(
                     $apiUrl,
        GuzzleClient $client = null,
                     $defaultTimeout = 20
    ) {
        $this->apiUrl = $apiUrl;
=======
    public function __construct(
        string $apiUrl,
        ConfigurationRepository $configurationRepository,
        ?AbstractGuzzleClient $client = null
    ) {
        $this->apiUrl = $apiUrl;
        $this->configurationRepository = $configurationRepository;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
        $this->client = $client;
        $this->defaultTimeout = $defaultTimeout;
    }

<<<<<<< HEAD
    /**
     * @return GuzzleClient
     */
    private function getClient()
    {
        if (null === $this->client) {
            $this->client = (new GuzzleClientFactory())->create([
                'name' => static::class,
                'base_uri' => $this->apiUrl,
                'headers' => $this->getHeaders(),
                'timeout' => $this->defaultTimeout,
            ]);
        }

        return $this->client;
    }

    /**
     * @param string $accessToken
     *
     * @return array
     *
     * $response['body']['userToken']
     * $response['body']['shopToken']
     */
    public function firebaseTokens($accessToken)
    {
        $this->getClient()->setRoute('v2/shop/firebase/tokens');

        return $this->getClient()->get([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ]),
        ]);
    }

    /**
     * @param string $refreshToken
     * @param string $shopUuid
     *
     * @return array response
     */
    public function refreshShopToken($refreshToken, $shopUuid)
    {
        $this->getClient()->setRoute('v1/shop/token/refresh');

        return $this->getClient()->post([
            'headers' => $this->getHeaders([
                'X-Shop-Id' => $shopUuid,
            ]),
            'json' => [
=======
    public function verifyToken(string $idToken): array
    {
        $this->getClient()->setRoute('shop/token/verify');

        return $this->getClient()->post([
            'json' => [
                'headers' => $this->getHeaders([
                    'X-Shop-Id' => $this->configurationRepository->getShopUuid(),
                ]),
                'token' => $idToken,
            ],
        ]);
    }

    public function refreshToken(string $refreshToken): array
    {
        $this->getClient()->setRoute('shop/token/refresh');

        return $this->getClient()->post([
            'json' => [
                'headers' => $this->getHeaders([
                    'X-Shop-Id' => $this->configurationRepository->getShopUuid(),
                ]),
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
                'token' => $refreshToken,
            ],
        ]);
    }

<<<<<<< HEAD
    /**
     * @param string $ownerUid
     * @param string $shopUid
     * @param string $ownerToken
     *
     * @return array
     */
    public function deleteUserShop($ownerUid, $shopUid, $ownerToken)
    {
        $this->getClient()->setRoute('v1/user/' . $ownerUid . '/shop/' . $shopUid);
=======
    public function deleteUserShop(string $ownerUid, string $shopUid, string $ownerToken): array
    {
        $this->client->setRoute('user/' . $ownerUid . '/shop/' . $shopUid);
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        return $this->getClient()->delete([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
<<<<<<< HEAD
                'X-Shop-Id' => $shopUid,
=======
                'X-Shop-Id' => $this->configurationRepository->getShopUuid(),
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
            ]),
        ]);
    }

<<<<<<< HEAD
    /**
     * @param string $shopUid
     * @param string $shopToken
     * @param array $payload
     *
     * @return array
     */
    public function reonboardShop($shopUid, $shopToken, $payload)
    {
        $this->getClient()->setRoute('v1/shop/' . $shopUid . '/reonboard');

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
        $this->getClient()->setRoute('v1/user/' . $ownerUid . '/shop/' . $shopUid);
=======
    public function reonboardShop(string $shopUid, string $shopToken, array $payload): array
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

    public function updateUserShop(string $ownerUid, string $shopUid, string $ownerToken, UpdateShop $shop): array
    {
        $this->getClient()->setRoute('user/' . $ownerUid . '/shop/' . $shopUid);
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        return $this->getClient()->patch([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
                'X-Shop-Id' => $shopUid,
            ]),
            'json' => $shop->jsonSerialize(),
        ]);
<<<<<<< HEAD
    }

    /**
     * @param string $shopUid
     * @param string $shopToken
     * @param UpgradeModule $data
     *
     * @return array
     */
    public function upgradeShopModule($shopUid, $shopToken, UpgradeModule $data)
    {
        $this->getClient()->setRoute('/v2/shop/module/update');

        return $this->getClient()->post([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $shopToken,
                'X-Shop-Id' => $shopUid,
            ]),
            'json' => $data->jsonSerialize(),
        ]);
=======
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }

    private function getHeaders(array $additionalHeaders = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'X-Module-Version' => \Ps_accounts::VERSION,
            'X-Prestashop-Version' => _PS_VERSION_,
        ], $additionalHeaders);
    }

<<<<<<< HEAD
    /**
     * @deprecated
     *
     * @param string $idToken
     *
     * @return array response
     */
    public function verifyToken($idToken)
    {
        $this->getClient()->setRoute('/v1/shop/token/verify');

        return $this->getClient()->post([
            'json' => [
                'token' => $idToken,
            ],
        ]);
=======
    private function getClient(): AbstractGuzzleClient
    {
        if (null === $this->client) {
            $this->client = (new GuzzleClientFactory())->create([
                'base_url' => $this->apiUrl,
                'defaults' => [
                    'headers' => $this->getHeaders(),
                ],
            ]);
        }

        return $this->client;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }
}
