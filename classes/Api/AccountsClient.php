<?php
/**
 * 2007-2020 PrestaShop and Contributors
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

namespace PrestaShop\Module\PsAccounts\Api;

use GuzzleHttp\Client;
use PrestaShop\AccountsAuth\Api\Client\GenericClient;
use PrestaShop\AccountsAuth\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Exception\FirebaseException;

/**
 * Construct the client used to make call to Accounts API
 */
class AccountsClient extends GenericClient
{
    public function __construct(\Link $link, Client $client = null)
    {
        parent::__construct();

        $this->setLink($link);
        $psAccountsService = new PsAccountsService();
        $token = $psAccountsService->getOrRefreshToken();

        if (!$token) {
            throw new FirebaseException('you must have admin token', 500);
        }

        // Client can be provided for tests
        if (null === $client) {
            $client = new Client([
                'base_url' => $_ENV['ACCOUNTS_SVC_API_URL'],
                'defaults' => [
                    'timeout' => $this->timeout,
                    'exceptions' => $this->catchExceptions,
                    'headers' => [
                        // Commented, else does not work anymore with API.
                        //'Content-Type' => 'application/vnd.accounts.v1+json', // api version to use
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $token,
                        'Shop-Id' => $psAccountsService->getCurrentShop()['id'],
                        'Hook-Url' => $this->link->getModuleLink(
                            'ps_accounts',
                            'DispatchWebHook',
                            [],
                            true,
                            null,
                            (int) $psAccountsService->getCurrentShop()['id']
                        ),
                        'Module-Version' => \Ps_accounts::VERSION, // version of the module
                        'Prestashop-Version' => _PS_VERSION_, // prestashop version
                    ],
                ],
            ]);
        }

        $this->setClient($client);
    }

    /**
     * @param array $headers
     * @param array $body
     *
     * @return array
     */
    public function checkWebhookAuthenticity(array $headers, array $body)
    {
        $correlationId = $headers['correlationId'];
        $this->setRoute($_ENV['ACCOUNTS_SVC_API_URL'] . '/webhooks/' . $correlationId . '/verify');

        $res = $this->post([
            'headers' => ['correlationId' => $correlationId],
            'json' => $body,
        ]);

        if (!$res || $res['httpCode'] < 200 || $res['httpCode'] > 299) {
            return [
                'httpCode' => $res['httpCode'],
                'body' => $res['body'] && is_array($res['body']) && array_key_exists('message', $res['body']) ? $res['body']['message'] : 'Unknown error',
            ];
        }

        return [
            'httpCode' => 200,
            'body' => 'ok',
        ];
    }
}
