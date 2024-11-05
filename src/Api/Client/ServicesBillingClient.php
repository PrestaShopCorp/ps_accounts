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

use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClient;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

/**
 * Handle call api Services
 */
class ServicesBillingClient
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * ServicesBillingClient constructor.
     *
     * @param string $apiUrl
     * @param PsAccountsService $psAccountsService
     * @param ShopProvider $shopProvider
     * @param GuzzleClient|null $client
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function __construct(
        $apiUrl,
        PsAccountsService $psAccountsService,
        ShopProvider $shopProvider,
        GuzzleClient $client = null
    ) {
        $shopId = $shopProvider->getCurrentShop()['id'];

        $token = $psAccountsService->getOrRefreshToken();

        // Client can be provided for tests
        if (null === $client) {
            $client = (new GuzzleClientFactory())->create([
                'base_uri' => $apiUrl,
                'headers' => [
                    // Commented, else does not work anymore with API.
                    //'Content-Type' => 'application/vnd.accounts.v1+json', // api version to use
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . (string) $token,
                    'Shop-Id' => $shopId,
                    'Module-Version' => \Ps_accounts::VERSION, // version of the module
                    'Prestashop-Version' => _PS_VERSION_, // prestashop version
                ],
            ]);
        }

        $this->client = $client;
    }

    /**
     * @param mixed $shopUuidV4
     *
     * @return array|false
     */
    public function getBillingCustomer($shopUuidV4)
    {
        $this->client->setRoute('/shops/' . $shopUuidV4);

        return $this->client->get();
    }

    /**
     * @param mixed $shopUuidV4
     * @param array $bodyHttp
     *
     * @return array|false
     */
    public function createBillingCustomer($shopUuidV4, $bodyHttp)
    {
        $this->client->setRoute('/shops/' . $shopUuidV4);

        return $this->client->post([
            'body' => $bodyHttp,
        ]);
    }

    /**
     * @param mixed $shopUuidV4
     * @param string $module
     *
     * @return array|false
     */
    public function getBillingSubscriptions($shopUuidV4, $module)
    {
        $this->client->setRoute('/shops/' . $shopUuidV4 . '/subscriptions/' . $module);

        return $this->client->get();
    }

    /**
     * @param mixed $shopUuidV4
     * @param string $module
     * @param array $bodyHttp
     *
     * @return array|false
     */
    public function createBillingSubscriptions($shopUuidV4, $module, $bodyHttp)
    {
        $this->client->setRoute('/shops/' . $shopUuidV4 . '/subscriptions/' . $module);

        return $this->client->post([
            'body' => $bodyHttp,
        ]);
    }
}
