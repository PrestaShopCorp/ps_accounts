<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Http\Client\Curl;

use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $baseUrl;

    protected function set_up()
    {
        parent::set_up();

        $scheme = $this->configuration->get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $domain = $this->configuration->get('PS_SHOP_DOMAIN');
        $this->baseUrl = $scheme . $domain . '/';

        $this->client = (new Factory())->create([
            ClientConfig::BASE_URI => $this->baseUrl,
            ClientConfig::TIMEOUT => 60,
            ClientConfig::SSL_CHECK => false,
            ClientConfig::ALLOW_REDIRECTS => true,
        ]);
    }

    /**
     * TODO: LegacyResponseClient
     * TODO: test curl options over PHP Versions
     * TODO: test with accounts-api timeout
     * TODO: test with oauth2 timeout
     * TODO: test ssl checks
     * TODO: test route
     */

    /**
     * @test
     */
    public function itShouldGetHttpResponseOk()
    {
        $this->client->getConfig()->sslCheck = true;

        $response = $this->client->get(
            '/index.php' //?fc=module&module=ps_accounts&controller=apiV2ShopHealthCheck'
        );

        $this->assertTrue((bool) preg_match(
            '@^' . $this->baseUrl . '@', $response->request->absUri
        ));

        $this->assertTrue($response->isSuccessful);
    }

    /**
     * @test
     */
    public function itShouldGetHttpsResponseOK()
    {
        $this->client->getConfig()->sslCheck = true;

        $response = $this->client->get('https://www.google.com');

        $this->assertTrue((bool) preg_match(
            '@^https://www.google.com@', $response->request->absUri
        ));

        $this->assertTrue($response->isSuccessful);
    }

//    /**
//     * @test
//     */
//    public function itShouldGetResponseUnsuccessful()
//    {
//        $this->client->getConfig()->sslCheck = true;
//
//        $response = $this->client->get('/foo-bar');
//
//        $this->assertFalse($response->isSuccessful);
//        $this->assertEquals(404, $response->statusCode);
//    }
}
