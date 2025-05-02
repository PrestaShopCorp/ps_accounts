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
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Vendor\Ramsey\Uuid\Uuid;

class AccountsService
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
            ClientConfig::HEADERS => $this->getHeaders(
                $config[ClientConfig::HEADERS] ? $config[ClientConfig::HEADERS] : []
            ),
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
            'X-Module-Version' => \Ps_accounts::VERSION,
            'X-Prestashop-Version' => _PS_VERSION_,
            'X-Multishop-Enabled' => \Shop::isFeatureActive() ? 'true' : 'false',
            'X-Request-ID' => Uuid::uuid4()->toString(),
        ], $additionalHeaders);
    }

    /**
     * @param string $accessToken
     *
     * @return FirebaseTokens
     *
     * @throws AccountsException
     */
    public function firebaseTokens($accessToken)
    {
        $response = $this->getClient()->get(
            'v2/shop/firebase/tokens',
            [
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ]),
            ]);

        if (!$response->isSuccessful) {
            throw new AccountsException($this->getResponseErrorMsg($response, 'Unable to refresh token.'));
        }

        return new FirebaseTokens($response->body);
    }

    /**
     * @param string $refreshToken
     * @param string $cloudShopId
     *
     * @return FirebaseTokens
     *
     * @throws AccountsException
     */
    public function refreshShopToken($refreshToken, $cloudShopId)
    {
        $response = $this->getClient()->post(
            'v1/shop/token/refresh',
            [
                Request::HEADERS => $this->getHeaders([
                    'X-Shop-Id' => $cloudShopId,
                ]),
                Request::JSON => [
                    'token' => $refreshToken,
                ],
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($this->getResponseErrorMsg($response, 'Unable to refresh token.'));
        }

        return new FirebaseTokens($response->body);
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
                    'Authorization' => 'Bearer ' . $ownerToken,
                    'X-Shop-Id' => $cloudShopId,
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
                    'Authorization' => 'Bearer ' . $ownerToken,
                    'X-Shop-Id' => $cloudShopId,
                ]),
                Request::JSON => $shop->jsonSerialize(),
            ]
        );
    }

//    /**
//     * @param string $cloudShopId
//     * @param string $shopToken
//     * @param UpgradeModule $data
//     *
//     * @return Response
//     */
//    public function upgradeShopModule($cloudShopId, $shopToken, UpgradeModule $data)
//    {
//        return $this->getClient()->post(
//            '/v2/shop/module/update',
//            [
//                Request::HEADERS => $this->getHeaders([
//                    'Authorization' => 'Bearer ' . $shopToken,
//                    'X-Shop-Id' => $cloudShopId,
//                ]),
//                Request::JSON => $data->jsonSerialize(),
//            ]
//        );
//    }

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
     *
     * @return IdentityCreated
     *
     * @throws AccountsException
     */
    public function createShopIdentity(ShopUrl $shopUrl)
    {
        $response = $this->getClient()->post(
            '/v1/shop-identities',
            [
                Request::JSON => [
                    'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
                    'frontendUrl' => $shopUrl->getFrontendUrl(),
                    'multiShopId' => $shopUrl->getMultiShopId(),
                ],
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($this->getResponseErrorMsg($response, 'Unable to create shop identity.'));
        }

        return new IdentityCreated($response->body);
    }

    /**
     * @param string $cloudShopId
     * @param string $shopToken
     * @param ShopUrl $shopUrl
     * @param string $proof
     *
     * @return ShopStatus
     *
     * @throws AccountsException
     */
    public function verifyShopIdentity($cloudShopId, $shopToken, ShopUrl $shopUrl, $proof)
    {
        $response = $this->getClient()->post(
            '/v1/shop-identities/' . $cloudShopId . '/verify', [
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
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($this->getResponseErrorMsg($response, 'Unable to verify shop identity.'));
        }

        return new ShopStatus($response->body);
    }

    /**
     * @param string $cloudShopId
     * @param string $shopToken
     *
     * @return ShopStatus
     *
     * @throws AccountsException
     */
    public function shopStatus($cloudShopId, $shopToken)
    {
        $response = $this->getClient()->get(
            '/v1/shop-status',
            [
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken,
                    'X-Shop-Id' => $cloudShopId,
                ]),
            ]
        );

        if (!$response->isSuccessful) {
            throw new AccountsException($this->getResponseErrorMsg($response, 'Unable to retrieve shop status'));
        }

        return new ShopStatus($response->body);
    }

    /**
     * @param Response $response
     * @param string $defaultMessage
     *
     * @return string
     */
    protected function getResponseErrorMsg(Response $response, $defaultMessage = '')
    {
        $msg = $defaultMessage;
        $body = $response->body;
        if (isset($body['error']) &&
            isset($body['error_description'])
        ) {
            $msg = $body['error'] . ': ' . $body['error_description'];
        }

        return $response->statusCode . ' - ' . $msg;
    }
}
