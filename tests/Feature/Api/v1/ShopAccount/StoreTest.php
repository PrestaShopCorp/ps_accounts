<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopAccount;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\BaseFeatureTest;

class StoreTest extends BaseFeatureTest
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceed()
    {
        $uuid = $this->faker->uuid;
        $email = $this->faker->safeEmail;

        $payload = [
            'shop_token' => $this->makeJwtToken(null, ['user_id' => $uuid]),
            'user_token' => $this->makeJwtToken(null, ['email' => $email]),
            'shop_refresh_token' => $this->makeJwtToken(),
        ];

        $response = $this->client->get('/module/ps_accounts/apiV1ShopAccount', [
            'query' => [
                AbstractRestController::PAYLOAD_PARAM => $this->encodePayload($payload)
            ],
        ]);

        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();

        print_r($json);

        $this->assertArraySubset(['success' => true], $json);

        $this->assertEquals($this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN), $payload['shop_token']);
        $this->assertEquals($this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN), $payload['shop_refresh_token']);
        $this->assertEquals($this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_EMAIL), $email);
        $this->assertEquals($this->configuration->get(Configuration::PSX_UUID_V4), $uuid);
    }
}
