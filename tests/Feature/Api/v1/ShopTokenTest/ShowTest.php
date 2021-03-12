<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopTokenTest;

use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\BaseFeatureTest;

class ShowTest extends BaseFeatureTest
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

        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();

        print_r($json);

        $this->assertArrayHasKey('domain', $json);
        $this->assertArrayHasKey('domain_ssl', $json);
    }
}
