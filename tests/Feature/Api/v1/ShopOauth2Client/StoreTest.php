<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopOauth2Client;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class StoreTest extends FeatureTestCase
{
    /**
     * @test
     */
    public function itShouldSucceed()
    {
        $payload = [
            'shop_id' => 1,
            'client_id' => $this->faker->slug,
            'client_secret' => $this->faker->password,
        ];

        $response = $this->client->post('/module/ps_accounts/apiV1ShopOauth2Client', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload($payload)
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);

        $this->assertArraySubset(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertEquals($payload['client_id'], $this->configuration->get(Configuration::PS_ACCOUNTS_OAUTH2_CLIENT_ID));
        $this->assertEquals($payload['client_secret'], $this->configuration->get(Configuration::PS_ACCOUNTS_OAUTH2_CLIENT_SECRET));
    }

    /**
     * @test
     */
    public function itShouldFail()
    {
        $payload = [
            'shop_id' => 1,
            // 'client_id' => $this->faker->slug,
            'client_secret' => $this->faker->password,
        ];

        $response = $this->client->post('/module/ps_accounts/apiV1ShopOauth2Client', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload($payload)
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseBadRequest($response);

        $this->assertArraySubset(['error' => true], $json);
    }
}
