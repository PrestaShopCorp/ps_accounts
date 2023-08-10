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
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;

/**
 * Class IndirectChannelClient
 */
class IndirectChannelClient extends GenericClient
{
    /**
     * ServicesAccountsClient constructor.
     *
     * @param string $apiUrl
     * @param Link $link
     * @param Client|null $client
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function __construct(
        $apiUrl,
        Link $link,
        Client $client = null
    ) {
        parent::__construct();

        $this->setLink($link->getLink());

        if (null === $client) {
            $client = new Client([
                'base_url' => $apiUrl,
                'defaults' => [
                    'timeout' => $this->timeout,
                    'exceptions' => $this->catchExceptions,
                    'headers' => $this->getHeaders(),
                ],
            ]);
        }

        $this->setClient($client);
    }

    /**
     * @param array $additionalHeaders
     *
     * @return array
     */
    private function getHeaders($additionalHeaders = [])
    {
        $userToken = $this->getUserTokenRepository();
        $shopToken = $this->getShopTokenRepository();

        return array_merge([
            'Accept' => 'application/json',
            'X-Module-Version' => \Ps_accounts::VERSION,
            'X-Prestashop-Version' => _PS_VERSION_,
            'Authorization' => 'Bearer ' . $userToken->getOrRefreshToken(),
            'X-Shop-Id' => $shopToken->getTokenUuid(),
        ], $additionalHeaders);
    }

    /**
     * @return array|null
     *
     * @throws \Exception
     */
    public function getInvitations()
    {
        $this->setRoute('invitations');

        return $this->get(['query' => ['pending' => 'true']]);
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
