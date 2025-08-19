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

namespace PrestaShop\Module\PsAccounts\Service\Accounts;

use PrestaShop\Module\PsAccounts\Account\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Account\ShopUrl;
use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\FirebaseTokens;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\IdentityCreated;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\LegacyFirebaseToken;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Vendor\Ramsey\Uuid\Uuid;

class AccountsService
{
    const HEADER_AUTHORIZATION = 'Authorization';
    const HEADER_MODULE_SOURCE = 'X-Module-Source';
    const HEADER_MODULE_VERSION = 'X-Module-Version';
    const HEADER_PRESTASHOP_VERSION = 'X-Prestashop-Version';
    const HEADER_MULTISHOP_ENABLED = 'X-Multishop-Enabled';
    const HEADER_REQUEST_ID = 'X-Request-ID';
    const HEADER_SHOP_ID = 'X-Shop-Id';

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
        $config[ClientConfig::HEADERS] = $this->getHeaders(
            isset($config[ClientConfig::HEADERS]) ? $config[ClientConfig::HEADERS] : []
        );

        $this->clientConfig = array_merge([
            ClientConfig::NAME => static::class,
        ], $config);
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if (null === $this->client) {
            $this->client = (new Factory())->create($this->clientConfig);
        }

        return $this->client;
    }

    /**
     * @param Client $client
     *
     * @return void
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
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
            self::HEADER_MODULE_VERSION => \Ps_accounts::VERSION,
            self::HEADER_PRESTASHOP_VERSION => _PS_VERSION_,
            self::HEADER_MULTISHOP_ENABLED => \Shop::isFeatureActive() ? 'true' : 'false',
            self::HEADER_REQUEST_ID => Uuid::uuid4()->toString(),
        ], $additionalHeaders);
    }

    /**
     * @param string $cloudShopId
     * @param string $accessToken
     *
     * @return FirebaseTokens
     *
     * @throws AccountsException
     */
    public function firebaseTokens($cloudShopId, $accessToken)
    {
        $response = $this->getClient()->get(
            '/v1/shop-identities/' . $cloudShopId . '/tokens',
            [
                Request::HEADERS => $this->getHeaders([
                    self::HEADER_AUTHORIZATION => 'Bearer ' . $accessToken,
                ]),
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($response, 'Unable to get firebase tokens', 'store-identity/unable-to-get-deprecated-tokens');
        }

        return new FirebaseTokens($response->body);
    }

    /**
     * @param string $refreshToken
     * @param string $cloudShopId
     *
     * @return LegacyFirebaseToken
     *
     * @throws AccountsException
     */
    public function refreshShopToken($refreshToken, $cloudShopId)
    {
        $response = $this->getClient()->post(
            'v1/shop/token/refresh',
            [
                Request::HEADERS => $this->getHeaders([
                    self::HEADER_SHOP_ID => $cloudShopId,
                ]),
                Request::JSON => [
                    'token' => $refreshToken,
                ],
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($response, 'Unable to refresh firebase shop token', 'store/unable-to-refresh-shop-token');
        }

        return new LegacyFirebaseToken($response->body);
    }

    /**
     * @param string $ownerUid
     * @param string $cloudShopId
     * @param string $ownerToken
     *
     * @return Response
     */
    public function deleteUserShop($ownerUid, $cloudShopId, $ownerToken)
    {
        return $this->getClient()->delete(
            'v1/user/' . $ownerUid . '/shop/' . $cloudShopId,
            [
                Request::HEADERS => $this->getHeaders([
                    self::HEADER_AUTHORIZATION => 'Bearer ' . $ownerToken,
                    self::HEADER_SHOP_ID => $cloudShopId,
                ]),
            ]
        );
    }

    /**
     * @param string $ownerUid
     * @param string $cloudShopId
     * @param string $ownerToken
     * @param UpdateShop $shop
     *
     * @return Response
     */
    public function updateUserShop($ownerUid, $cloudShopId, $ownerToken, UpdateShop $shop)
    {
        return $this->getClient()->patch(
            'v1/user/' . $ownerUid . '/shop/' . $cloudShopId,
            [
                Request::HEADERS => $this->getHeaders([
                    // FIXME: use shop access token instead
                    self::HEADER_AUTHORIZATION => 'Bearer ' . $ownerToken,
                    self::HEADER_SHOP_ID => $cloudShopId,
                ]),
                Request::JSON => $shop->jsonSerialize(),
            ]
        );
    }

    /**
     * @param string $idToken
     *
     * @return Response
     *
     * @deprecated since v8.0.0
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
        );
    }

    /**
     * @return Response
     */
    public function healthCheck()
    {
        return $this->getClient()->get('/healthcheck');
    }

    /**
     * @param ShopUrl $shopUrl
     * @param string|null $proof
     * @param string|null $source
     *
     * @return IdentityCreated
     *
     * @throws AccountsException
     */
    public function createShopIdentity(ShopUrl $shopUrl, $proof = null, $source = null)
    {
        $response = $this->getClient()->post(
            '/v1/shop-identities',
            [
                Request::HEADERS => $this->getHeaders([
                    self::HEADER_MODULE_SOURCE => $source,
                ]),
                Request::JSON => array_merge(
                    [
                        'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
                        'frontendUrl' => $shopUrl->getFrontendUrl(),
                        'multiShopId' => $shopUrl->getMultiShopId(),
                    ],
                    $proof ? ['proof' => $proof] : []
                ),
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($response, 'Unable to create shop identity', 'store-identity/unable-to-create-shop-identity');
        }

        return new IdentityCreated($response->body);
    }

    /**
     * @param string $cloudShopId
     * @param string $shopToken
     * @param ShopUrl $shopUrl
     * @param string $proof
     * @param string|null $source
     *
     * @return void
     *
     * @throws AccountsException
     */
    public function verifyShopIdentity($cloudShopId, $shopToken, ShopUrl $shopUrl, $proof, $source = null)
    {
        $response = $this->getClient()->post(
            '/v1/shop-identities/' . $cloudShopId . '/verify',
            [
                Request::HEADERS => $this->getHeaders([
                    self::HEADER_AUTHORIZATION => 'Bearer ' . $shopToken,
                    self::HEADER_SHOP_ID => $cloudShopId,
                    self::HEADER_MODULE_SOURCE => $source,
                ]),
                Request::JSON => [
                    'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
                    'frontendUrl' => $shopUrl->getFrontendUrl(),
                    'multiShopId' => $shopUrl->getMultiShopId(),
                    'proof' => $proof,
                ],
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($response, 'Unable to verify shop identity', 'store-identity/unable-to-verify-shop-identity');
        }
    }

    /**
     * @param string $cloudShopId
     * @param string $shopToken
     * @param string|null $source
     *
     * @return ShopStatus
     *
     * @throws AccountsException
     */
    public function shopStatus($cloudShopId, $shopToken, $source = null)
    {
        $response = $this->getClient()->get(
            '/v1/shop-identities/' . $cloudShopId . '/status',
            [
                Request::HEADERS => $this->getHeaders([
                    self::HEADER_AUTHORIZATION => 'Bearer ' . $shopToken,
                    self::HEADER_SHOP_ID => $cloudShopId,
                    self::HEADER_MODULE_SOURCE => $source,
                ]),
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($response, 'Unable to retrieve shop status', 'store-identity/unable-to-retrieve-shop-status');
        }

        return new ShopStatus($response->body);
    }

    /**
     * @param string $cloudShopId
     * @param string $shopToken
     * @param string $userToken
     * @param string|null $source
     *
     * @return void
     *
     * @throws AccountsException
     */
    public function setPointOfContact($cloudShopId, $shopToken, $userToken, $source = null)
    {
        $response = $this->getClient()->post(
            '/v1/shop-identities/' . $cloudShopId . '/point-of-contact',
            [
                Request::HEADERS => $this->getHeaders([
                    self::HEADER_AUTHORIZATION => 'Bearer ' . $shopToken,
                    self::HEADER_SHOP_ID => $cloudShopId,
                    self::HEADER_MODULE_SOURCE => $source,
                ]),
                Request::JSON => [
                    'pointOfContactJWT' => $userToken,
                ],
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($response, 'Unable to set point of contact', 'store-identity/unable-to-set-point-of-contact');
        }
    }

    /**
     * @param string $cloudShopId
     * @param string $shopToken
     * @param ShopUrl $shopUrl
     * @param string $fromVersion
     * @param string|null $proof
     * @param string|null $source
     *
     * @return IdentityCreated
     *
     * @throws AccountsException
     */
    public function migrateShopIdentity($cloudShopId, $shopToken, ShopUrl $shopUrl, $fromVersion, $proof = null, $source = null)
    {
        $response = $this->getClient()->put(
            '/v1/shop-identities/' . $cloudShopId . '/migrate',
            [
                Request::HEADERS => $this->getHeaders([
                    self::HEADER_AUTHORIZATION => 'Bearer ' . $shopToken,
                    self::HEADER_SHOP_ID => $cloudShopId,
                    self::HEADER_MODULE_SOURCE => $source,
                ]),
                Request::JSON => array_merge(
                    [
                        'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
                        'frontendUrl' => $shopUrl->getFrontendUrl(),
                        'multiShopId' => $shopUrl->getMultiShopId(),
                        'fromVersion' => $fromVersion,
                    ],
                    $proof ? ['proof' => $proof] : []
                ),
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($response, 'Unable to migrate shop identity', 'store-identity/unable-to-migrate-shop-identity');
        }

        return new IdentityCreated($response->body);
    }
}
