<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopOauth2Client;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Api\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;
use function GuzzleHttp\Psr7\str;

class StoreTest extends FeatureTestCase
{
    /**
     * @inject
     *
     * @var Oauth2Client
     */
    protected $oauth2Client;

    /**
     * @inject
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * @inject
     *
     * @var OwnerSession
     */
    protected $ownerSession;

    /**
     * @test
     */
    public function itShouldSucceed()
    {
        $expiry = new \DateTimeImmutable('+10 days');

        $payload = [
            'shop_id' => 1,
            'client_id' => $this->faker->slug,
            // FIXME: something's wrong there
            'client_secret' => preg_replace('/</', '', $this->faker->password),
            'uid' => $this->faker->uuid,
        ];

        $response = $this->client->post('/module/ps_accounts/apiV1ShopOauth2Client', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload($payload)
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);
        $this->assertBodySubset(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertEquals($payload['client_id'], $this->oauth2Client->getClientId());
        $this->assertEquals($payload['client_secret'], $this->oauth2Client->getClientSecret());
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

        if ($response->getStatusCode() !== 400) {
            // trigger incomplete test status with some PHP5.6 environments
            $this->assertBodySubset(['error' => true], $json);
        }

        $this->assertResponseBadRequest($response);

        $this->assertArraySubset(['error' => true], $json);
    }
}
