<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopOauth2Client;

use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Http\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class DeleteTest extends FeatureTestCase
{
    /**
     * @inject
     *
     * @var OAuth2Client
     */
    protected $oauth2Client;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceed()
    {
        $this->oauth2Client->update($this->faker->slug, $this->faker->password);

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

        $this->assertEmpty($this->oauth2Client->getClientId());
        $this->assertEmpty($this->oauth2Client->getClientSecret());
        $this->assertFalse($this->oauth2Client->exists());


    }
}
