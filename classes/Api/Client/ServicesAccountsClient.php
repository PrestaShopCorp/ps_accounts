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
use PrestaShop\Module\PsAccounts\Exception\TokenNotFoundException;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\ShopTokenService;

/**
 * Class ServicesAccountsClient
 */
class ServicesAccountsClient extends GenericClient
{
    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var ShopTokenService
     */
    private $shopTokenService;

    /**
     * ServicesAccountsClient constructor.
     *
     * @param array $config
     * @param ShopProvider $shopProvider
     * @param ShopTokenService $shopTokenService
     * @param Link $link
     * @param Client|null $client
     *
     * @throws OptionResolutionException
     * @throws TokenNotFoundException
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function __construct(
        array $config,
        ShopProvider $shopProvider,
        ShopTokenService $shopTokenService,
        Link $link,
        Client $client = null
    ) {
        parent::__construct();

        $config = $this->resolveConfig($config);

        $this->shopProvider = $shopProvider;
        $this->shopTokenService = $shopTokenService;

        $shopId = (int) $this->shopProvider->getCurrentShop()['id'];
        $token = $this->shopTokenService->getOrRefreshToken();

        $this->setLink($link->getLink());

        if (!$token) {
            throw new TokenNotFoundException('Firebase token not found');
        }

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
     * @param array $bodyHttp
     *
     * @return array | false
     */
    public function updateShopUrl($shopUuidV4, $bodyHttp)
    {
        $this->setRoute('/shops/' . $shopUuidV4 . '/url');

        return $this->patch([
            'body' => $bodyHttp,
        ]);
    }

    /**
     * @param string $shopUuidV4
     *
     * @return array
     */
    public function deleteShop($shopUuidV4)
    {
        $this->setRoute('/shop/' . $shopUuidV4);

        return $this->delete();
    }

    /**
     * @param array $headers
     * @param array $body
     *
     * @return array
     *
     * @throws \PrestaShopException
     */
    public function verifyWebhook(array $headers, array $body)
    {
        $correlationId = $headers['correlationId'];

        $this->setRoute('/webhooks/' . $correlationId . '/verify');

        $shopId = (int) $this->shopProvider->getCurrentShop()['id'];
        $hookUrl = $this->link->getModuleLink('ps_accounts', 'DispatchWebHook', [], true, null, $shopId);

        $res = $this->post([
            'headers' => [
                'correlationId' => $correlationId,
                'Hook-Url' => $hookUrl,
            ],
            'json' => $body,
        ]);

        if (!$res || $res['httpCode'] < 200 || $res['httpCode'] > 299) {
            return [
                'httpCode' => $res['httpCode'],
                'body' => $res['body']
                && is_array($res['body'])
                && array_key_exists('message', $res['body'])
                    ? $res['body']['message']
                    : 'Unknown error',
            ];
        }

        return [
            'httpCode' => 200,
            'body' => 'ok',
        ];
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
