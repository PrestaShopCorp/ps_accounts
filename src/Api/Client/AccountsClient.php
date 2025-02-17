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
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClient;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Vendor\Ramsey\Uuid\Uuid;

class AccountsClient
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var GuzzleClient
     */
    private $client;

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
        $this->client = $client;
        $this->defaultTimeout = $defaultTimeout;
    }

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
                'token' => $refreshToken,
            ],
        ]);
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
        $this->getClient()->setRoute('v1/user/' . $ownerUid . '/shop/' . $shopUid);

        return $this->getClient()->delete([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
                'X-Shop-Id' => $shopUid,
            ]),
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

        return $this->getClient()->patch([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
                'X-Shop-Id' => $shopUid,
            ]),
            'json' => $shop->jsonSerialize(),
        ]);
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
    }

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
            'headers' => $this->getHeaders(),
            'json' => [
                'token' => $idToken,
            ],
        ]);
    }

    /**
     * @return array
     */
    public function healthCheck()
    {
        $this->getClient()->setRoute('/healthcheck');

        return $this->getClient()->get();
    }
}
