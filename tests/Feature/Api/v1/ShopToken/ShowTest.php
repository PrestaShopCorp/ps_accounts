<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopToken;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
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
        $response = $this->client->get('/module/ps_accounts/apiV1ShopToken', [
            'query' => [
                AbstractRestController::PAYLOAD_PARAM => $this->encodePayload([
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = $response->json();

        print_r($json);

        $this->assertResponseOk($response);

        $this->assertArraySubset([
            'token' => $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN),
            'refresh_token' => $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN),
        ], $json);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnInvalidPayloadError()
    {
        $response = $this->client->get('/module/ps_accounts/apiV1ShopToken', [
            'query' => [
                AbstractRestController::PAYLOAD_PARAM => $this->encodePayload([
                        'shop_id' => 1,
                    ]) . 'foo'
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
        $response = $this->client->get('/module/ps_accounts/apiV1ShopToken', [
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
