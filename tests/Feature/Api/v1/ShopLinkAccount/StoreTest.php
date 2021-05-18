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
        $shopUuid = $this->faker->uuid;
        $userUuid = $this->faker->uuid;
        $email = $this->faker->safeEmail;
        $employeeId = $this->faker->numberBetween(1);

        $expiry = new \DateTimeImmutable('+10 days');

        $payload = [
            'shop_id' => 1,
            'shop_token' => (string) $this->makeJwtToken($expiry, ['user_id' => $shopUuid]),
            'user_token' => (string) $this->makeJwtToken($expiry, ['user_id' => $userUuid,'email' => $email]),
            'shop_refresh_token' => (string) $this->makeJwtToken($expiry),
            'user_refresh_token' => (string) $this->makeJwtToken($expiry),
            'employee_id' => $employeeId,
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

        $this->assertEquals($userUuid, $this->configuration->get(Configuration::PS_ACCOUNTS_USER_FIREBASE_UUID));
        $this->assertEquals($payload['user_token'], $this->configuration->get(Configuration::PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN));
        $this->assertEquals($payload['user_refresh_token'], $this->configuration->get(Configuration::PS_ACCOUNTS_USER_FIREBASE_REFRESH_TOKEN));

        $this->assertEquals($email, $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_EMAIL));
        $this->assertEquals($shopUuid, $this->configuration->get(Configuration::PSX_UUID_V4));
        $this->assertEquals($employeeId, $this->configuration->get(Configuration::PS_ACCOUNTS_EMPLOYEE_ID));
    }
}
