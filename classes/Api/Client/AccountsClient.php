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
     * @param array $config
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
        ShopProvider $shopProvider,
        Link $link,
        Client $client = null
    ) {
        parent::__construct();

        $config = $this->resolveConfig($config);

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
     * FIXME: pass user bearer NOT shop one
     *
     * @param string $userUuid
     * @param string $shopUuidV4
     *
     * @return array
     */
    public function deleteUserShop($userUuid, $shopUuidV4)
    {
        $this->setRoute('/user/' . $userUuid . '/shop/' . $shopUuidV4);

        return $this->delete([
            'headers' => [
// FIXME
//                'Authorization' => 'Bearer ' .
            ]
        ]);
    }

    /**
     * @param $idToken
     *
     * @return array response
     */
    public function verifyToken($idToken)
    {
        $this->setRoute('/shop/token/verify');

        return $this->post([
            'json' => [
                'token' => $idToken,
            ],
        ]);
    }

    /**
     * @param $refreshToken
     *
     * @return array response
     */
    public function refreshToken($refreshToken)
    {
        $this->setRoute('/shop/token/refresh');

        return $this->post([
            'json' => [
                'refreshToken' => $refreshToken,
            ],
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
