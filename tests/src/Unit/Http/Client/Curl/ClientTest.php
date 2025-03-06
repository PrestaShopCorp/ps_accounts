<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Http\Client\Curl;

use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class ClientTest extends TestCase
{
    /**
     * @test
     *
     * TODO: LegacyResponseClient
     * TODO: test curl options over PHP Versions
     * TODO: test with accounts-api timeout
     * TODO: test with oauth2 timeout
     * TODO: test with wrong url (404)
     * TODO: test ssl checks
     */
    public function itShouldGetResponseOk()
    {
        $scheme = $this->configuration->get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $domain = $this->configuration->get('PS_SHOP_DOMAIN');
        $baseUrl = $scheme . $domain . '/';

        $httpClient = (new Factory())->create([
            ClientConfig::BASE_URI => $baseUrl,
            ClientConfig::TIMEOUT => 60,
            ClientConfig::SSL_CHECK => false,
            ClientConfig::ALLOW_REDIRECTS => true,
        ]);

        $response = $httpClient->get(
            '/index.php?fc=module&module=ps_accounts&controller=apiV2ShopHealthCheck',
            [
                Request::HEADERS => [
                    //'Accept' => 'application/json',
                ],
            ]
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful);
    }
}
