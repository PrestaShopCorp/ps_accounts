<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopUrl;

use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class ShowTest extends FeatureTestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceed()
    {
        $response = $this->client->get('/module/ps_accounts/apiV1ShopUrl', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => $this->encodePayload([
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = $response->json();

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);

        $this->assertArrayHasKey('domain', $json);
        $this->assertArrayHasKey('domain_ssl', $json);
        $this->assertArrayHasKey('physical_uri', $json);
        $this->assertArrayHasKey('virtual_uri', $json);
        $this->assertArrayHasKey('ssl_activated', $json);

        $this->assertInternalType('string', $json['domain']);
        $this->assertInternalType('string', $json['domain_ssl']);
        $this->assertInternalType('string', $json['physical_uri']);
        $this->assertInternalType('string', $json['virtual_uri']);
        $this->assertInternalType('bool', $json['ssl_activated']);
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
                AbstractRestController::TOKEN_HEADER => $this->encodePayload([
                    'shop_id' => 1,
                ]) . 'foobar'
            ],
        ]);

        $json = $response->json();

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
                AbstractRestController::TOKEN_HEADER => $this->encodePayload([
                    'shop_id' => 99,
                ]),
            ],
        ]);

        $json = $response->json();

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseUnauthorized($response);

        $this->assertArraySubset([
            'error' => true,
        ], $json);
    }
}
