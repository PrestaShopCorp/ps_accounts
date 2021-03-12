<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature;

use GuzzleHttp\Client;
use Module;
use PrestaShop\Module\PsAccounts\Service\ShopKeysService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class BaseFeatureTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        parent::setUp();

        $this->client = new Client([
            'defaults' => [
                'timeout' => 60,
                'exceptions' => true,
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
        /** @var \Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        /** @var ShopKeysService $shopKeysService */
        $shopKeysService = $module->getService(ShopKeysService::class);

        return base64_encode($shopKeysService->encrypt(json_encode($payload)));
    }
}
