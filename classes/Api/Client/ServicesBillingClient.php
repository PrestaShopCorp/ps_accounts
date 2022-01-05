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
use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

/**
 * Handle call api Services
 */
class ServicesBillingClient extends AbstractGenericApiClient
{
    /**
     * ServicesBillingClient constructor.
     *
     * @param string $apiUrl
     * @param PsAccountsService $psAccountsService
     * @param ShopProvider $shopProvider
     * @param Link $link
     * @param AbstractGuzzleClient|null $client
     *
     * @throws OptionResolutionException
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function __construct(
        $apiUrl,
        PsAccountsService $psAccountsService,
        ShopProvider $shopProvider,
        Link $link,
        AbstractGuzzleClient $client = null
    ) {
        parent::__construct();

        $shopId = $shopProvider->getCurrentShop()['id'];

        $token = $psAccountsService->getOrRefreshToken();

        $this->setLink($link->getLink());

        // Client can be provided for tests
        if (null === $client) {
            $client = $this->createClient([
                'base_url' => $apiUrl,
                'defaults' => [
                    'timeout' => $this->timeout,
                    'exceptions' => $this->catchExceptions,
                    'headers' => [
                        // Commented, else does not work anymore with API.
                        //'Content-Type' => 'application/vnd.accounts.v1+json', // api version to use
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . (string) $token,
                        'Shop-Id' => $shopId,
                        'Module-Version' => \Ps_accounts::VERSION, // version of the module
                        'Prestashop-Version' => _PS_VERSION_, // prestashop version
                    ],
                ],
            ]);
        }

        $this->setClient($client);
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
