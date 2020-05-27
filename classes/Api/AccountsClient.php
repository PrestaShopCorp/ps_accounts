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

/**
 * Construct the client used to make call to Accounts API
 */
class AccountsClient extends GenericClient
{
    public function __construct(\Link $link, Client $client = null)
    {
        $this->setLink($link);

        // Client can be provided for tests
        if (null === $client) {
            $client = new Client([
                'base_url' => getenv('ACCOUNTS_API_URL'),
                'defaults' => [
                    'timeout' => $this->timeout,
                    'exceptions' => $this->catchExceptions,
                    'headers' => [
                        // Commented, else does not work anymore with API.
                        //'Content-Type' => 'application/vnd.accounts.v1+json', // api version to use
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->getToken(),
                        //'Shop-Id' => (new ShopUuidManager())->getForShop((int) \Context::getContext()->shop->id),
                        'Hook-Url' => $this->link->getModuleLink(
                            'ps_accounts',
                            'DispatchWebHook',
                            [],
                            true,
                            null,
                            (int) \Context::getContext()->shop->id
                        ),
                        'Module-Version' => \Ps_accounts::VERSION, // version of the module
                        'Prestashop-Version' => _PS_VERSION_, // prestashop version
                    ],
                ],
            ]);
        }

        $this->setClient($client);
    }

    private function getToken()
    {
        // TODO : a rendre dynamique !
        return 'eyJhbGciOiJSUzI1NiIsImtpZCI6ImY1YzlhZWJlMjM0ZGE2MDE2YmQ3Yjk0OTE2OGI4Y2Q1YjRlYzllZWIiLCJ0eXAiOiJKV1QifQ.eyJuYW1lIjoiUHJlc3RhU2hvcCIsImlzcyI6Imh0dHBzOi8vc2VjdXJldG9rZW4uZ29vZ2xlLmNvbS9wcmVzdGFzaG9wLXJlYWR5LWludGVncmF0aW9uIiwiYXVkIjoicHJlc3Rhc2hvcC1yZWFkeS1pbnRlZ3JhdGlvbiIsImF1dGhfdGltZSI6MTU5MDE1NjY2NywidXNlcl9pZCI6Ik91aG52WHV3UGtRR0ptUkpoTURqaURIWGdndTEiLCJzdWIiOiJPdWhudlh1d1BrUUdKbVJKaE1EamlESFhnZ3UxIiwiaWF0IjoxNTkwMTU2NjY3LCJleHAiOjE1OTAxNjAyNjcsImVtYWlsIjoidmljZXMtaW50ZWdyYXRpb25fcHJlc3Rhc2hvcF9uZXQtNjBmYWQ2ZGI2QHBzYWNjb3VudHMucHNlc3NlbnRpYWxzLm5ldCIsImVtYWlsX3ZlcmlmaWVkIjpmYWxzZSwiZmlyZWJhc2UiOnsiaWRlbnRpdGllcyI6eyJlbWFpbCI6WyJ2aWNlcy1pbnRlZ3JhdGlvbl9wcmVzdGFzaG9wX25ldC02MGZhZDZkYjZAcHNhY2NvdW50cy5wc2Vzc2VudGlhbHMubmV0Il19LCJzaWduX2luX3Byb3ZpZGVyIjoicGFzc3dvcmQifX0.mGfQBlXtZAFJXa7zfIa6ikE0ezwAQLLdcqHsuK7v89neF4FMEFc-mkegOAWq9lvSnwNrE1BXsu_CtYWAu568xbhhdRPcfHr-f5oXJlSvl3kCFOJyjn1f2lsh2muIFk0mPs0T3Gmq5lWLT7CW_AwLGqyXkuoZDRN8xpff08RP6bk5d4ZIP-jqz3GW3D5NRB80Cio1YuZjSAyRn30VYoW3c4SGVXyEyOehfWhe-2VGkOGjoTZ3DaqTq3XjaykolUCnFQwlp5ofLVx9cSHSidCdELB5pPIF-cKC7aQNF_VdRO_O_qGcttY2YNiiN3ecMA-cIPqTA3pvA9Wp2aqlCSVWww';
    }

    public function checkWebhookAuthenticity(array $headers, array $body)
    {
        $correlationId = $headers['correlationId'];
        $this->setRoute(getenv('ACCOUNTS_API_URL') . '/webhook/' . $correlationId . '/verify');

        $res = $this->post([
            'headers' => ['correlationId' => $correlationId],
            'json' => $body,
        ]);

        if (!$res || $res['httpCode'] < 200 || $res['httpCode'] > 299) {
        dump($res); die;
            return [
                'httpCode' => $res['httpCode'],
                'body' => $res['body'] ? $res['body']['message'] : 'Unknown error',
            ];
        }

        return [
            'httpCode' => 200,
            'body' => 'ok',
        ];
    }
}
