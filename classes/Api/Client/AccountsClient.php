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
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
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
     * @throws OptionResolutionException
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

        $config = $this->resolveConfig(['api_url' => $apiUrl]);

        $this->shopProvider = $shopProvider;

        $shopId = (int) $this->shopProvider->getCurrentShop()['id'];

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
     * @param string $userUuid
     * @param string $shopUuidV4
     *
     * @return array
     *
     * @throws \Exception
     */
    public function deleteUserShop($userUuid, $shopUuidV4)
    {
        $this->setRoute('user/' . $userUuid . '/shop/' . $shopUuidV4);

        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var UserTokenRepository $userTokenRepository */
        $userTokenRepository = $module->getService(UserTokenRepository::class);

        return $this->delete([
            'headers' => [
                'Authorization' => 'Bearer ' . $userTokenRepository->getOrRefreshToken(),
            ],
        ]);
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
                'token' => $refreshToken,
            ],
        ]);
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
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var ShopTokenRepository $shopTokenRepository */
        $shopTokenRepository = $module->getService(ShopTokenRepository::class);

        /** @var ConfigurationRepository $configurationRepository */
        $configurationRepository = $module->getService(ConfigurationRepository::class);

        $shopId = $configurationRepository->getShopId();

        $configurationRepository->setShopId($currentShop['id']);

        $this->setRoute('shop/' . $currentShop['uuid'] . '/reonboard');

        $response = $this->post([
            'headers' => [
                'Authorization' => 'Bearer ' . $shopTokenRepository->getOrRefreshToken(),
                'content-type' => 'application/json',
            ],
            'json' => $currentShop,
        ]);

        $configurationRepository->setShopId($shopId);

        return $response;
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
