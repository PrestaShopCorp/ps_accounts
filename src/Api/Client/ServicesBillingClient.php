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

use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

/**
 * Handle call api Services
 *
 * @deprecated since v7.0.0
 */
class ServicesBillingClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * ServicesBillingClient constructor.
     *
     * @param string $apiUrl
     * @param PsAccountsService $psAccountsService
     * @param ShopProvider $shopProvider
     * @param Client|null $client
     *
     * @throws \PrestaShopException
     */
    public function __construct(
        $apiUrl,
        PsAccountsService $psAccountsService,
        ShopProvider $shopProvider,
        Client $client = null
    ) {
        $shopId = $shopProvider->getCurrentShop()['id'];

        $token = $psAccountsService->getOrRefreshToken();

        // Client can be provided for tests
        if (null === $client) {
            $client = (new Factory())->create([
                ClientConfig::BASE_URI => $apiUrl,
                ClientConfig::NAME => static::class,
                ClientConfig::HEADERS => [
                    // Commented, else does not work anymore with API.
                    //'Content-Type' => 'application/vnd.accounts.v1+json', // api version to use
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . (string) $token,
                    'Shop-Id' => $shopId,
                    'Module-Version' => \Ps_accounts::VERSION, // version of the module
                    'Prestashop-Version' => _PS_VERSION_, // prestashop version
                ],
                ClientConfig::TIMEOUT => 20,
                ClientConfig::SSL_CHECK => true,
            ]);
        }

        $this->client = $client;
    }

    /**
     * @param mixed $shopUuidV4
     *
     * @return array
     */
    public function getBillingCustomer($shopUuidV4)
    {
        return $this->client->get('/shops/' . $shopUuidV4)
            ->toLegacy();
    }

    /**
     * @param mixed $shopUuidV4
     * @param array $bodyHttp
     *
     * @return array
     */
    public function createBillingCustomer($shopUuidV4, $bodyHttp)
    {
        return $this->client->post(
            '/shops/' . $shopUuidV4,
            [
                Request::FORM => $bodyHttp,
            ]
        )->toLegacy();
    }

    /**
     * @param mixed $shopUuidV4
     * @param string $module
     *
     * @return array
     */
    public function getBillingSubscriptions($shopUuidV4, $module)
    {
        return $this->client->get('/shops/' . $shopUuidV4 . '/subscriptions/' . $module)
            ->toLegacy();
    }

    /**
     * @param mixed $shopUuidV4
     * @param string $module
     * @param array $bodyHttp
     *
     * @return array
     */
    public function createBillingSubscriptions($shopUuidV4, $module, $bodyHttp)
    {
        return $this->client->post(
            '/shops/' . $shopUuidV4 . '/subscriptions/' . $module,
            [
                Request::FORM => $bodyHttp,
            ]
        )->toLegacy();
    }
}
