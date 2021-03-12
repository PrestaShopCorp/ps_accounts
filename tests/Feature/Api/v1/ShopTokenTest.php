<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1;

use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\BaseFeatureTest;

class ShopTokenTest extends BaseFeatureTest
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldShowWithSuccess()
    {
        $baseUri = 'http://prestashop-17-herve-dev.local';

        $response = $this->client->get($baseUri . '/module/ps_accounts/apiV1ShopUrl', [
            'query' => [
                AbstractRestController::PAYLOAD_PARAM => $this->encodePayload([
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = json_decode($response->getBody()->getContents(), true);

        print_r($json);

        $this->assertArrayHasKey('domain', $json);
        $this->assertArrayHasKey('domain_ssl', $json);
    }
}
