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
use PrestaShop\Module\PsAccounts\Account\Dto\UpgradeModule;
use PrestaShop\Module\PsAccounts\Account\ShopUrl;
use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Vendor\Ramsey\Uuid\Uuid;

class AccountsClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    protected $clientConfig;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->clientConfig = array_merge([
            ClientConfig::NAME => static::class,
            ClientConfig::HEADERS => $this->getHeaders(),
        ], $config);
    }

    /**
     * @return Client
     */
    private function getClient()
    {
        if (null === $this->client) {
            $this->client = (new Factory())->create($this->clientConfig);
        }

        return $this->client;
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
            'X-Multishop-Enabled' => (bool) \Shop::isFeatureActive(),
            'X-Request-ID' => Uuid::uuid4()->toString(),
        ], $additionalHeaders);
    }

    /**
     * @param string $accessToken
     *
     * @return array
     *
     * $response['body']['userToken']
     * $response['body']['userRefreshToken']
     * $response['body']['shopToken']
     * $response['body']['shopRefreshToken']
     */
    public function firebaseTokens($accessToken)
    {
        return $this->getClient()->get(
            'v2/shop/firebase/tokens',
            [
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ]),
            ])->toLegacy();
    }

    /**
     * @param string $refreshToken
     * @param string $cloudShopId
     *
     * @return array
     */
    public function refreshShopToken($refreshToken, $cloudShopId)
    {
        return $this->getClient()->post(
            'v1/shop/token/refresh',
            [
                Request::HEADERS => $this->getHeaders([
                    'X-Shop-Id' => $cloudShopId,
                ]),
                Request::JSON => [
                    'token' => $refreshToken,
                ],
            ]
        )->toLegacy();
    }

    /**
     * @param string $ownerUid
     * @param string $cloudShopId
     * @param string $ownerToken
     *
     * @return array
     */
    public function deleteUserShop($ownerUid, $cloudShopId, $ownerToken)
    {
        return $this->getClient()->delete(
            'v1/user/' . $ownerUid . '/shop/' . $cloudShopId,
            [
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $ownerToken,
                    'X-Shop-Id' => $cloudShopId,
                ]),
            ]
        )->toLegacy();
    }

    /**
     * @param string $ownerUid
     * @param string $cloudShopId
     * @param string $ownerToken
     * @param UpdateShop $shop
     *
     * @return array
     */
    public function updateUserShop($ownerUid, $cloudShopId, $ownerToken, UpdateShop $shop)
    {
        return $this->getClient()->patch(
            'v1/user/' . $ownerUid . '/shop/' . $cloudShopId,
            [
                Request::HEADERS => $this->getHeaders([
                    // FIXME: use shop access token instead
                    'Authorization' => 'Bearer ' . $ownerToken,
                    'X-Shop-Id' => $cloudShopId,
                ]),
                Request::JSON => $shop->jsonSerialize(),
            ]
        )->toLegacy();
    }

    /**
     * @param string $cloudShopId
     * @param string $shopToken
     * @param UpgradeModule $data
     *
     * @return array
     */
    public function upgradeShopModule($cloudShopId, $shopToken, UpgradeModule $data)
    {
        return $this->getClient()->post(
            '/v2/shop/module/update',
            [
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken,
                    'X-Shop-Id' => $cloudShopId,
                ]),
                Request::JSON => $data->jsonSerialize(),
            ]
        )->toLegacy();
    }

    /**
     * @deprecated
     *
     * @param string $idToken
     *
     * @return array
     */
    public function verifyToken($idToken)
    {
        return $this->getClient()->post(
            '/v1/shop/token/verify',
            [
//                Request::HEADERS => $this->getHeaders(),
                Request::JSON => [
                    'token' => $idToken,
                ],
            ]
        )->toLegacy();
    }

    /**
     * @return array
     */
    public function healthCheck()
    {
        return $this->getClient()->get('/healthcheck')->toLegacy();
    }

    /**
     * @param ShopUrl $shopUrl
     *
     * @return array
     */
    public function createShopIdentity(ShopUrl $shopUrl)
    {
        return $this->getClient()->post(
            '/v1/shop-identities',
            [
                Request::JSON => [
                    'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
                    'frontendUrl' => $shopUrl->getFrontendUrl(),
                    'multiShopId' => $shopUrl->getMultiShopId(),
                ],
            ]
        )->toLegacy();
    }

    /**
     * @param string $cloudShopId
     * @param string $shopToken
     * @param ShopUrl $shopUrl
     * @param string $proof
     *
     * @return array
     */
    public function verifyShopProof($cloudShopId, $shopToken, ShopUrl $shopUrl, $proof)
    {
        return $this->getClient()->put(
            '/v1/shop-verifications/' . $cloudShopId, [
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken,
                    'X-Shop-Id' => $cloudShopId,
                ]),
                Request::JSON => [
                    'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
                    'frontendUrl' => $shopUrl->getFrontendUrl(),
                    'multiShopId' => $shopUrl->getMultiShopId(),
                    'proof' => $proof,
                ],
            ]
        )->toLegacy();
    }

    /**
     * @param string $cloudShopId
     * @param string $shopToken
     *
     * @return array
     */
    public function shopStatus($cloudShopId, $shopToken)
    {
        return $this->getClient()->get(
            '/v1/shop-status',
            [
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken,
                    'X-Shop-Id' => $cloudShopId,
                ]),
            ]
        )->toLegacy();
    }
}
