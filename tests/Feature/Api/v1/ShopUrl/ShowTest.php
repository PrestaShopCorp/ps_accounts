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
            'query' => [
                AbstractRestController::PAYLOAD_PARAM => $this->encodePayload([
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = $response->json();

        print_r($json);

        $this->assertResponseOk($response);

        $this->assertArrayHasKey('domain', $json);
        $this->assertArrayHasKey('domain_ssl', $json);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnInvalidPayloadError()
    {
        $response = $this->client->get('/module/ps_accounts/apiV1ShopUrl', [
            'query' => [
                AbstractRestController::PAYLOAD_PARAM => $this->encodePayload([
                    'shop_id' => 1,
                ]) . 'foobar'
            ],
        ]);

        $json = $response->json();

        print_r($json);

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
    public function itShouldReturnNotFoundError()
    {
        $response = $this->client->get('/module/ps_accounts/apiV1ShopUrl', [
            'query' => [
                AbstractRestController::PAYLOAD_PARAM => $this->encodePayload([
                    'shop_id' => 99,
                ])
            ],
        ]);

        $json = $response->json();

        print_r($json);

        $this->assertResponseNotFound($response);

        $this->assertArraySubset([
            'error' => true,
        ], $json);
    }
}
