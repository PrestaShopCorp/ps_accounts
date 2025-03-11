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
     * @param string $shopUuid
     *
     * @return array
     */
    public function refreshShopToken($refreshToken, $shopUuid)
    {
        return $this->getClient()->post(
            'v1/shop/token/refresh',
            [
                Request::HEADERS => $this->getHeaders([
                    'X-Shop-Id' => $shopUuid,
                ]),
                Request::JSON => [
                    'token' => $refreshToken,
                ],
            ]
        )->toLegacy();
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
        return $this->getClient()->delete(
            'v1/user/' . $ownerUid . '/shop/' . $shopUid,
            [
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $ownerToken,
                    'X-Shop-Id' => $shopUid,
                ]),
            ]
        )->toLegacy();
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
        return $this->getClient()->patch(
            'v1/user/' . $ownerUid . '/shop/' . $shopUid,
            [
                Request::HEADERS => $this->getHeaders([
                    // FIXME: use shop access token instead
                    'Authorization' => 'Bearer ' . $ownerToken,
                    'X-Shop-Id' => $shopUid,
                ]),
                Request::JSON => $shop->jsonSerialize(),
            ]
        )->toLegacy();
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
        return $this->getClient()->post(
            '/v2/shop/module/update',
            [
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken,
                    'X-Shop-Id' => $shopUid,
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
                Request::HEADERS => $this->getHeaders(),
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
}
