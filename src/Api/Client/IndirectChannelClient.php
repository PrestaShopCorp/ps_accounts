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

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClient;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClientFactory;

/**
 * Class IndirectChannelClient
 */
class IndirectChannelClient
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
     * @param array $additionalHeaders
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getHeaders($additionalHeaders = [])
    {
        $userToken = $this->getOwnerSession();
        $shopToken = $this->getShopSession();

        return array_merge([
            'Accept' => 'application/json',
            'X-Module-Version' => \Ps_accounts::VERSION,
            'X-Prestashop-Version' => _PS_VERSION_,
            'Authorization' => 'Bearer ' . $userToken->getOrRefreshToken(),
            'X-Shop-Id' => $shopToken->getToken()->getUuid(),
        ], $additionalHeaders);
    }

    /**
     * @return GuzzleClient
     *
     * @throws \Exception
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
     * @return array|null
     *
     * @throws \Exception
     */
    public function getInvitations()
    {
        $this->getClient()->setRoute('invitations');

        return $this->getClient()->get(['query' => ['pending' => 'true']]);
    }

    /**
     * @return ShopSession
     *
     * @throws \Exception
     */
    private function getShopSession()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        return $module->getService(ShopSession::class);
    }

    /**
     * @return OwnerSession
     *
     * @throws \Exception
     */
    private function getOwnerSession()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        return $module->getService(OwnerSession::class);
    }
}
