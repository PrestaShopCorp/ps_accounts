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
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Vendor\Ramsey\Uuid\Uuid;

class AccountsClient
{
    /**
     * @var string
     */
    private $baseUri;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var int
     */
    private $defaultTimeout;

    /**
     * @var bool
     */
    protected $sslCheck;

    /**
     * ServicesAccountsClient constructor.
     *
     * @param string $baseUri
     * @param int $defaultTimeout
     * @param bool $sslCheck
     *
     * @throws \Exception
     */
    public function __construct(
        $baseUri,
        $defaultTimeout = 20,
        $sslCheck = true
    ) {
        $this->baseUri = $baseUri;
        $this->defaultTimeout = $defaultTimeout;
        $this->sslCheck = $sslCheck;
    }

    /**
     * @return Client
     */
    private function getClient()
    {
        if (null === $this->client) {
            $this->client = (new Factory())->create([
                'name' => static::class,
                'baseUri' => $this->baseUri,
                'headers' => $this->getHeaders(),
                'timeout' => $this->defaultTimeout,
                'sslCheck' => $this->sslCheck,
            ]);
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
     * $response['body']['shopToken']
     */
    public function firebaseTokens($accessToken)
    {
        /** @var array $res */
        $res = $this->getClient()->get(
            'v2/shop/firebase/tokens',
            [
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ]),
            ]);

        return $res;
    }

    /**
     * @param string $refreshToken
     * @param string $shopUuid
     *
     * @return array
     */
    public function refreshShopToken($refreshToken, $shopUuid)
    {
        /** @var array $res */
        $res = $this->getClient()->post(
            'v1/shop/token/refresh',
            [
                'headers' => $this->getHeaders([
                    'X-Shop-Id' => $shopUuid,
                ]),
                'json' => [
                    'token' => $refreshToken,
                ],
            ]
        );

        return $res;
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
        /** @var array $res */
        $res = $this->getClient()->delete(
            'v1/user/' . $ownerUid . '/shop/' . $shopUid,
            [
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $ownerToken,
                    'X-Shop-Id' => $shopUid,
                ]),
            ]
        );

        return $res;
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
        /** @var array $res */
        $res = $this->getClient()->patch(
            'v1/user/' . $ownerUid . '/shop/' . $shopUid,
            [
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $ownerToken,
                    'X-Shop-Id' => $shopUid,
                ]),
                'json' => $shop->jsonSerialize(),
            ]
        );

        return $res;
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
        /** @var array $res */
        $res = $this->getClient()->post(
            '/v2/shop/module/update',
            [
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken,
                    'X-Shop-Id' => $shopUid,
                ]),
                'json' => $data->jsonSerialize(),
            ]
        );

        return $res;
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
        /** @var array $res */
        $res = $this->getClient()->post(
            '/v1/shop/token/verify',
            [
                'headers' => $this->getHeaders(),
                'json' => [
                    'token' => $idToken,
                ],
            ]
        );

        return $res;
    }

    /**
     * @return array
     */
    public function healthCheck()
    {
        /** @var array $res */
        $res = $this->getClient()->get('/healthcheck');

        return $res;
    }
}
