<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\V2\ShopHealthCheck;

use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\Feature\Api\V2\TestCase;

class ShowTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldShowPublicHealthCheck()
    {
        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        $response = $this->client->get('/module/ps_accounts/apiV2ShopHealthCheck', [
            'query' => [
                'shop_id' => $shop->id,
            ]
        ]);

        $json = $this->getResponseJson($response);

        $this->assertResponseOk($response);

        $this->assertIsBool($json['oauth2Client']);
        $this->assertIsBool($json['shopLinked']);
        $this->assertIsBool($json['isSsoEnabled']);
        $this->assertIsBool($json['fopenActive']);
        $this->assertIsBool($json['curlActive']);
        $this->assertIsBool($json['oauthApiConnectivity']);
        $this->assertIsBool($json['accountsApiConnectivity']);

        $this->assertIsArray($json['oauthToken']);
        $this->assertIsArray($json['firebaseOwnerToken']);
        $this->assertIsArray($json['firebaseShopToken']);

        $this->assertIsInt($json['serverUTC']);
        $this->assertIsInt($json['mysqlUTC']);

        $this->assertIsString($json['env']['oauth2Url']);
        $this->assertIsString($json['env']['accountsApiUrl']);
        $this->assertIsString($json['env']['accountsUiUrl']);
        $this->assertIsString($json['env']['accountsCdnUrl']);
        $this->assertIsString($json['env']['testimonialsUrl']);
        $this->assertIsBool($json['env']['checkApiSslCert']);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldShowPrivateHealthCheck()
    {
        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        $response = $this->client->get('/module/ps_accounts/apiV2ShopHealthCheck', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->makeBearer([
                        'aud' => [
                            'ps_accounts/' . $shop->uuid,
                        ],
                        'scp' => [
                            'shop.health',
                        ]
                    ]),
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->assertResponseOk($response);

        $this->assertEquals(_PS_VERSION_, $json['psVersion']);
        $this->assertEquals(\Ps_accounts::VERSION, $json['moduleVersion']);
        $this->assertEquals(phpversion(), $json['phpVersion']);

        $this->assertIsBool($json['oauth2Client']);

        // Private info
        $this->assertIsString($json['cloudShopId']);
        $this->assertIsString($json['shopName']);
        $this->assertIsString($json['ownerEmail']);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldFailWithInvalidBearer()
    {
        $response = $this->client->get('/module/ps_accounts/apiV2ShopHealthCheck', [
            'headers' => [
                'Authorization' => 'Bearer: ' . 'some-invalid-bearer',
            ],
            'query' => [
                'shop_id' => 1,
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->assertResponseUnauthorized($response);

        $this->assertArraySubset([
            'error' => true,
            'message' => 'Invalid token',
        ], $json);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldFailWithInvalidAudience()
    {
        $response = $this->client->get('/module/ps_accounts/apiV2ShopHealthCheck', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->makeBearer([
                        'aud' => [
                            'ps_accounts/' . 'invalid_uid',
                        ],
                    ]),
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->assertResponseUnauthorized($response);

        $this->assertArraySubset([
            'error' => true,
            'message' => 'Invalid audience',
        ], $json);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldFailWithInvalidScope()
    {
        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        $response = $this->client->get('/module/ps_accounts/apiV2ShopHealthCheck', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->makeBearer([
                        'aud' => [
                            'ps_accounts/' . $shop->uuid,
                        ],
                        'scp' => [
                            'shop.health-foo',
                        ]
                    ]),
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->assertResponseUnauthorized($response);

        $this->assertArraySubset([
            'error' => true,
            'message' => 'Invalid scope',
        ], $json);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldFailWithInvalidShopId()
    {
        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        $response = $this->client->get('/module/ps_accounts/apiV2ShopHealthCheck', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->makeBearer([
                        'aud' => [
                            'ps_accounts/' . $shop->uuid,
                        ],
                        'scp' => [
                            'shop.health',
                        ]
                    ]),
            ],
            'query' => [
                'shop_id' => 99,
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->assertResponseNotFound($response);

        $this->assertArraySubset([
            'error' => true,
            //'message' => 'Invalid audience',
        ], $json);
    }
}
