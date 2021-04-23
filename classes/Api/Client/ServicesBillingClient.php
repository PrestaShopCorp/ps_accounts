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
use PrestaShop\Module\PsAccounts\Configuration\ConfigOptionsResolver;
use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

/**
 * Handle call api Services
 */
class ServicesBillingClient extends GenericClient
{
    /**
     * ServicesBillingClient constructor.
     *
     * @param array $config
     * @param PsAccountsService $psAccountsService
     * @param ShopProvider $shopProvider
     * @param Link $link
     * @param Client|null $client
     *
     * @throws OptionResolutionException
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function __construct(
        array $config,
        PsAccountsService $psAccountsService,
        ShopProvider $shopProvider,
        Link $link,
        Client $client = null
    ) {
        parent::__construct();

        $config = $this->resolveConfig($config);

        $shopId = $shopProvider->getCurrentShop()['id'];

        $token = $psAccountsService->getOrRefreshToken();

        $this->setLink($link->getLink());

        // Client can be provided for tests
        if (null === $client) {
            $client = new Client([
                'base_url' => $config['api_url'],
                'defaults' => [
                    'timeout' => $this->timeout,
                    'exceptions' => $this->catchExceptions,
                    'headers' => [
                        // Commented, else does not work anymore with API.
                        //'Content-Type' => 'application/vnd.accounts.v1+json', // api version to use
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $token,
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
     * @return array | false
     */
    public function getBillingCustomer($shopUuidV4)
    {
        $this->setRoute('/shops/' . $shopUuidV4);

        return $this->get();
    }

    /**
     * @param mixed $shopUuidV4
     * @param array $bodyHttp
     *
     * @return array | false
     */
    public function createBillingCustomer($shopUuidV4, $bodyHttp)
    {
        $this->setRoute('/shops/' . $shopUuidV4);

        return $this->post([
            'body' => $bodyHttp,
        ]);
    }

    /**
     * @param mixed $shopUuidV4
     * @param string $module
     *
     * @return array | false
     */
    public function getBillingSubscriptions($shopUuidV4, $module)
    {
        $this->setRoute('/shops/' . $shopUuidV4 . '/subscriptions/' . $module);

        return $this->get();
    }

    /**
     * @param mixed $shopUuidV4
     * @param string $module
     * @param array $bodyHttp
     *
     * @return array | false
     */
    public function createBillingSubscriptions($shopUuidV4, $module, $bodyHttp)
    {
        $this->setRoute('/shops/' . $shopUuidV4 . '/subscriptions/' . $module);

        return $this->post([
            'body' => $bodyHttp,
        ]);
    }

    /**
     * @param array $config
     * @param array $defaults
     *
     * @return array
     *
     * @throws OptionResolutionException
     */
    public function resolveConfig(array $config, array $defaults = [])
    {
        return (new ConfigOptionsResolver([
            'api_url',
        ]))->resolve($config, $defaults);
    }
}
