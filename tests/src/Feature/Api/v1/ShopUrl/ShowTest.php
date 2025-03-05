<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopUrl;

use PrestaShop\Module\PsAccounts\Http\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class ShowTest extends FeatureTestCase
{
    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopContext;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceed()
    {
        $shop = $this->shopContext->formatShopData((array) \Shop::getShop(1));

        $response = $this->client->get('/module/ps_accounts/apiV1ShopUrl', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload([
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);

        $this->assertEquals($shop->domain, $json['domain']);
        $this->assertEquals($shop->domainSsl, $json['domain_ssl']);
        $this->assertEquals($shop->physicalUri, $json['physical_uri']);
        $this->assertEquals($shop->virtualUri, $json['virtual_uri']);
        $this->assertEquals($this->configurationRepository->sslEnabled(), $json['ssl_activated']);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnInvalidPayloadError()
    {
        $response = $this->client->get('/module/ps_accounts/apiV1ShopUrl', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload([
                    'shop_id' => 1,
                ]) . 'foobar'
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseUnauthorized($response);

        $this->assertArraySubset([
            'error' => true,
        ], $json);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnUnauthorizedError()
    {
        $response = $this->client->get('/module/ps_accounts/apiV1ShopUrl', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload([
                    'shop_id' => 99,
                ]),
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseUnauthorized($response);

        $this->assertArraySubset([
            'error' => true,
        ], $json);
    }
}
