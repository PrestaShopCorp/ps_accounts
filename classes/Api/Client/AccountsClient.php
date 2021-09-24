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

use GuzzleHttp\Client;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\DTO\UpdateShop;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;

/**
 * Class ServicesAccountsClient
 */
class AccountsClient extends GenericClient
{
    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * ServicesAccountsClient constructor.
     *
     * @param string $apiUrl
     * @param ShopProvider $shopProvider
     * @param Link $link
     * @param Client|null $client
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function __construct(
        $apiUrl,
        ShopProvider $shopProvider,
        Link $link,
        Client $client = null
    ) {
        parent::__construct();

        $this->shopProvider = $shopProvider;

        $this->setLink($link->getLink());

        if (null === $client) {
            $client = new Client([
                'base_url' => $apiUrl,
                'defaults' => [
                    'timeout' => $this->timeout,
                    'exceptions' => $this->catchExceptions,
                ],
            ]);
        }

        $this->setClient($client);
    }

    /**
     * @param string $idToken
     *
     * @return array response
     */
    public function verifyToken($idToken)
    {
        $this->setRoute('shop/token/verify');

        return $this->post([
            'json' => [
                'headers' => $this->getHeaders(),
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
        $this->setRoute('shop/token/refresh');

        return $this->post([
            'json' => [
                'headers' => $this->getHeaders(),
                'token' => $refreshToken,
            ],
        ]);
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
        return $this->shopProvider->getShopContext()->execInShopContext($shopId, function () {
            $userToken = $this->getUserTokenRepository();
            $shopToken = $this->getShopTokenRepository();

            $this->setRoute('user/' . $userToken->getTokenUuid() . '/shop/' . $shopToken->getTokenUuid());

            return $this->delete([
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $userToken->getOrRefreshToken(),
                ]),
            ]);
        });
    }

    /**
     * @param UpdateShop $shop
     *
     * @return array
     *
     * @throws \Exception
     */
    public function updateUserShop(UpdateShop $shop)
    {
        return $this->shopProvider->getShopContext()->execInShopContext($shop->shopId, function () use ($shop) {
            $userToken = $this->getUserTokenRepository();
            $shopToken = $this->getShopTokenRepository();

            $this->setRoute('user/' . $userToken->getTokenUuid() . '/shop/' . $shopToken->getTokenUuid());

            /** @var \Ps_accounts $module */
            $module = \Module::getInstanceByName('ps_accounts');

            $module->getLogger()->info('### - updateUserShop ' . 'user/' . $userToken->getTokenUuid() . '/shop/' . $shopToken->getTokenUuid());
            $module->getLogger()->info('### - updateUserShop ' . json_encode($shop));

            return $this->patch([
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $userToken->getOrRefreshToken(),
                    'content-type' => 'application/json',
                ]),
                'json' => $shop->jsonSerialize(),
            ]);
        });
    }

    /**
     * @param array $currentShop
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function reonboardShop($currentShop)
    {
        return $this->shopProvider->getShopContext()->execInShopContext($currentShop['id'], function () use ($currentShop) {
            $shopToken = $this->getShopTokenRepository();

            $this->setRoute('shop/' . $currentShop['uuid'] . '/reonboard');

            return $this->post([
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken->getOrRefreshToken(),
                    'content-type' => 'application/json',
                ]),
                'json' => $currentShop,
            ]);
        });
    }

    /**
     * @param array $additionalHeaders
     *
     * @return array
     */
    private function getHeaders($additionalHeaders = [])
    {
        $shopId = (int) $this->shopProvider->getCurrentShop()['id'];

        return array_merge([
            'Accept' => 'application/json',
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
