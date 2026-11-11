<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Http\Client\Curl;

use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Exception\ClientException;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
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
     * TODO: test curl options over PHP Versions
     * TODO: test with accounts-api timeout
     * TODO: test with oauth2 timeout
     * TODO: test ssl checks
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

    /**
     * @test
     */
    public function itShouldGetLegacyFormatResponseOK()
    {
        $this->client->getConfig()->sslCheck = true;

        $response = $this->client->get('/index.php');

        $this->assertTrue($response->isSuccessful);

        $legacy = $response->toLegacy();

        $this->assertArrayHasKey('status', $legacy);
        $this->assertArrayHasKey('httpCode', $legacy);
        $this->assertArrayHasKey('body', $legacy);
    }

    /**
     * @test
     */
    public function itShouldPreserveNativeCurlResponsesWithoutDeprecations()
    {
        $file = tempnam(sys_get_temp_dir(), 'ps-accounts-curl-');
        file_put_contents($file, '{"synthetic":true}');
        $request = new Request([Request::HANDLER => curl_init('file://' . $file)]);
        curl_setopt($request->handler, CURLOPT_RETURNTRANSFER, true);

        try {
            $response = $this->getResponseWithoutDeprecations($request);
            $this->assertSame('{"synthetic":true}', $response->raw);
            $this->assertSame(['synthetic' => true], $response->body);
            $this->assertSame($request, $response->request);
            $this->assertSame(0, $response->statusCode);
        } finally {
            unlink($file);
        }
    }

    /**
     * @test
     */
    public function itShouldPreserveNativeCurlErrorsWithoutDeprecations()
    {
        $request = new Request([Request::HANDLER => curl_init('unsupported://synthetic')]);
        curl_setopt($request->handler, CURLOPT_RETURNTRANSFER, true);
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(CURLE_UNSUPPORTED_PROTOCOL);
        $this->getResponseWithoutDeprecations($request);
    }

    private function getResponseWithoutDeprecations(Request $request)
    {
        $method = new \ReflectionMethod(Client::class, 'getResponse');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }
        set_error_handler(function ($level, $message) {
            if ($level === E_DEPRECATED) {
                throw new \ErrorException($message, 0, $level);
            }

            return false;
        });
        try {
            return $method->invoke($this->client, $request);
        } finally {
            restore_error_handler();
        }
    }
}
