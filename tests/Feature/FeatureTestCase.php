<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature;

use Db;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
use PrestaShop\Module\PsAccounts\Service\ShopKeysService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class FeatureTestCase extends TestCase
{
    /**
     * @var bool
     */
    protected $enableTransactions = false;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        $scheme = $this->configuration->get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $domain = $this->configuration->get('PS_SHOP_DOMAIN');
        $baseUrl = $scheme . $domain;

        $this->client = new Client([
            'base_url' => $baseUrl,
            'defaults' => [
                'timeout' => 60,
                'exceptions' => false,
                'allow_redirects' => false,
                'query' => [],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        ]);
    }

    /**
     * @param array $payload
     *
     * @return string
     *
     * @throws \Exception
     */
    public function encodePayload(array $payload)
    {
        /** @var ShopKeysService $shopKeysService */
        $shopKeysService = $this->module->getService(ShopKeysService::class);

        return base64_encode($shopKeysService->encrypt(json_encode($payload)));
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseOk(ResponseInterface $response)
    {
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseUnauthorized(ResponseInterface $response)
    {
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseNotFound(ResponseInterface $response)
    {
        $this->assertEquals(404, $response->getStatusCode());
    }
}
