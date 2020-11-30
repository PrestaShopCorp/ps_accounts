<?php
/**
 * 2007-2020 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Api\Client;

use GuzzleHttp\Client;
use Link;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Exception\FirebaseException;

/**
 * Handle call api Services
 */
class ServicesAccountsClient extends GenericClient
{
    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * ServicesAccountsClient constructor.
     *
     * @param array $config
     * @param PsAccountsService $psAccountsService
     * @param Link $link
     * @param Client|null $client
     *
     * @throws FirebaseException
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function __construct(
        array $config,
        PsAccountsService $psAccountsService,
        Link $link,
        Client $client = null
    ) {
        parent::__construct();

        $this->psAccountsService = $psAccountsService;

        $shopId = (int) $psAccountsService->getCurrentShop()['id'];
        $token = $psAccountsService->getOrRefreshToken();

        $this->setLink($link);

        if (!$token) {
            throw new FirebaseException('Firebase token not found', 500);
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
    public function changeUrl($shopUuidV4, $bodyHttp)
    {
        $this->setRoute('/shops/' . $shopUuidV4 . '/url');

        return $this->patch([
            'body' => $bodyHttp,
        ]);
    }

    /**
     * @param array $headers
     * @param array $body
     *
     * @return array
     */
    public function verifyWebhook(array $headers, array $body)
    {
        $correlationId = $headers['correlationId'];

        $this->setRoute('/webhooks/' . $correlationId . '/verify');

        $shopId = (int) $this->psAccountsService->getCurrentShop()['id'];
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
}
