<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopOauth2Client;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class DeleteTest extends FeatureTestCase
{
    /**
     * @test
     */
    public function itShouldSucceed()
    {
        $this->configuration->set(Configuration::PS_ACCOUNTS_OAUTH2_CLIENT_ID, $this->faker->slug);
        $this->configuration->set(Configuration::PS_ACCOUNTS_OAUTH2_CLIENT_SECRET, $this->faker->password);

        $response = $this->client->delete('/module/ps_accounts/apiV1ShopOauth2Client', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload([
                    'method' => 'DELETE',
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseDeleted($response);

        // FIXME: empty response
        // $this->assertArraySubset(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertEmpty($this->configuration->get(Configuration::PS_ACCOUNTS_OAUTH2_CLIENT_ID));
        $this->assertEmpty($this->configuration->get(Configuration::PS_ACCOUNTS_OAUTH2_CLIENT_SECRET));
    }
}
