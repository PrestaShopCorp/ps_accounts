<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopLinkAccount;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class DeleteTest extends FeatureTestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceed()
    {
        $this->configuration->set(Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN, 'foobar');

        $response = $this->client->delete('/module/ps_accounts/apiV1ShopLinkAccount', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload([
                    'method' => 'DELETE',
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = $response->json();

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseDeleted($response);

        // FIXME: empty response
        // $this->assertArraySubset(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertEmpty($this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN));
        $this->assertEmpty($this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN));

        $this->assertEmpty($this->configuration->get(Configuration::PS_ACCOUNTS_USER_FIREBASE_UUID));
        $this->assertEmpty($this->configuration->get(Configuration::PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN));
        $this->assertEmpty($this->configuration->get(Configuration::PS_ACCOUNTS_USER_FIREBASE_REFRESH_TOKEN));

        $this->assertEmpty($this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_EMAIL));
        $this->assertEmpty($this->configuration->get(Configuration::PSX_UUID_V4));
        $this->assertEmpty($this->configuration->get(Configuration::PS_ACCOUNTS_EMPLOYEE_ID));
    }
}
