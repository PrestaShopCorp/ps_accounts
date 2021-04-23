<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopLinkAccount;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class StoreTest extends FeatureTestCase
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

        $expiry = new \DateTimeImmutable('+10 days');

        $payload = [
            'shop_id' => 1,
            'shop_token' => (string) $this->makeJwtToken($expiry, ['user_id' => $uuid]),
            'user_token' => (string) $this->makeJwtToken($expiry, ['email' => $email]),
            'shop_refresh_token' => (string) $this->makeJwtToken($expiry),
            'user_refresh_token' => (string) $this->makeJwtToken($expiry),
        ];

        $response = $this->client->post('/module/ps_accounts/apiV1ShopLinkAccount', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => $this->encodePayload($payload)
            ],
        ]);

        $json = $response->json();

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);

        $this->assertArraySubset(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertEquals($payload['shop_token'], $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN));
        $this->assertEquals($payload['shop_refresh_token'], $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN));
        $this->assertEquals($email, $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_EMAIL));
        $this->assertEquals($uuid, $this->configuration->get(Configuration::PSX_UUID_V4));
    }
}
