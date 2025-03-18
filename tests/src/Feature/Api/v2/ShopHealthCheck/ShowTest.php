<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v2\ShopHealthCheck;

use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\OAuth2\CachedFile;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\JWT;

class ShowTest extends FeatureTestCase
{
    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @var string
     */
    private $wellKnown = <<<JSON
{
    "authorization_endpoint": "https://oauth.foo.bar/oauth2/auth",
    "token_endpoint": "https://oauth.foo.bar/oauth2/token",
    "userinfo_endpoint": "https://oauth.foo.bar/userinfo",
    "jwks_uri": "https://oauth.foo.bar/.well-known/jwks.json"
}
JSON;

    /**
     * openssl genrsa
     *
     * @var string
     */
    private $privateKey = <<<EOD
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC0P0TcLUx+gsEx
sQh7HDuwg7sgelLt0++6s8ZMH5+5+cNa1uOU7Fe0jo2R5BYPmFcOKNfDwXpmpm8V
ixEMZRbC7x85NDImrfaK6TEbMz6z2hrb6pH/8HLFKCi/H48Pn3YzqrF6u8vVd6PH
PR+g1UDfsSx17hJQPd7hSJkS1DIIUfsvpK7WpW1AuzMiLWkj2VsSdnGK2ONFu4rq
X7IIvENRHhS7O+ZYB8wEVMp/MMGvcxKUOHEAzVRVuWhyvdRLyp4PaNMuHh/FbaQU
FdGKPdJfXYKtwwN4YYMEqsA9EqKgJttEHLO2XjJoDAMv3LF3lHQ6uOUCV7yeaEJe
dGJZr3rHAgMBAAECggEAVtWOInZOPjpWwU40tD5/QQPNYBGeJwYtnSfjdaPcirdZ
Fkek/wc3H3x3lluoBx5QfjlN7PgmNBKlPF8xOOPwabvSMnhDWt//AJ/j8OMop3OA
8gZZDNB1MictLhoRjSi4xJ5Mf0C+q5nGFIONW6mw5bMQpMWrG+4alDSpzejdK6Un
ZLUAOCmtJPwNZOFUC5j+OmKt55u7mYU45egJFwjKda8iSOoOv2nM+cy8N7Poe+YB
Fo8ZM9rLQaEXQ6Axda4A2PIErCXABiGmjoaYIRMe5ldjqJ/XOMVN4whvGLH3gaFz
wrfu5QXY1u2N0o9URdNuQOzY1RJyisaK624RdKnYmQKBgQDptINQYV0b71dm9nbI
Iyxlk7lGGjdOJGpwuMU/POoVt+RXDQzPS+2c47S2mHyZm+DTnIPOs4Fj2uGu7K6l
ZHCBTMzZyWwZ/BVdrhFiiezVM88MachA5kbJPbRQd1pg8rwyi4tM4gp+5dg4U3ZO
CVJZC4SsMcoKl+pDARILrqSC6QKBgQDFcTgCDOFtvH22dQwci7JQ4Hkt1nNIRwSU
qCGdOwLq06Mb3Lsl11U607IloWrHXbKZKPZPLQFZmtjzLiEDQfVwbzddfDTDi+kk
C9Gxy9VOB9t1ZuLH6tE9Gt53pPxmvFcggw43WfqArvP4REERMNcQQ36HT948BOLT
tKuaKwSiLwKBgQCnuS9MrrOd6sV1VYil1eh09fHPx9zNLgFd6J1W5yawc4tiljEN
TNa49AqryQATVfWiqP9HhzhjK6EwYxhsBotfoHicDJySgYHr+5Lqf0YDNO1xYTGg
siM3JhuUbDPmxA4g0Fkm0krqC5aDxhJvquz4jvWmhw0TUTeE4u3KiIn0kQKBgGdA
Lenkkn6wc41W6F6FL1rcCILQArlpahvTUMDIe14SDtojNDs1wHxi+GZ1Y0Ge3ib6
JNSC1JHnSEzjcqAhfhiuEGXy7iTUpbcT9zTjQ0jrEvjyNOYXBhTOWEh5HwUauPVn
k6eehkGFPFA4YU58G/uFUEWynqUEaRoNATA+Ds5BAoGBAMnqeKcccS6s0VRahYwZ
kW1wJGQs49HkmX2D2B66ADeN4THYY1fkTzRxBLIfpFL4YRv7cZ1LmHip0Ytf3sGM
eET4miTFf9JT6T9O4bIU9O/gGl2ugHJ5kOlZStUY5NQORjceJr3SCqrQGS8fTwnH
lvk7RWHQCyzkwJ18oYPEe7K1
-----END PRIVATE KEY-----
EOD;

    // https://pem2jwk.vercel.app/
    private $jwks = <<<JSON
{
  "keys": [
    {
      "kty": "RSA",
      "n": "tD9E3C1MfoLBMbEIexw7sIO7IHpS7dPvurPGTB-fufnDWtbjlOxXtI6NkeQWD5hXDijXw8F6ZqZvFYsRDGUWwu8fOTQyJq32iukxGzM-s9oa2-qR__ByxSgovx-PD592M6qxervL1Xejxz0foNVA37Esde4SUD3e4UiZEtQyCFH7L6Su1qVtQLszIi1pI9lbEnZxitjjRbuK6l-yCLxDUR4UuzvmWAfMBFTKfzDBr3MSlDhxAM1UVblocr3US8qeD2jTLh4fxW2kFBXRij3SX12CrcMDeGGDBKrAPRKioCbbRByztl4yaAwDL9yxd5R0OrjlAle8nmhCXnRiWa96xw",
      "e": "AQAB",
      "ext": true,
      "kid": "public:hydra.jwt.access-token",
      "alg": "RS256",
      "use": "sig"
    }
  ]
}
JSON;

    /**
     * @var CachedFile
     */
    private $cachedJwks;

    /**
     * @var CachedFile
     */
    private $cachedWellKnown;

    public function set_up()
    {
        // useless to write wellknown cause JWKS is already present
        //$this->writeWellKnown();

        $this->writeJwks();

        parent::set_up();
    }

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
                            'shop_' . $shop->uuid,
                        ],
                        'scp' => [
                            'shop.health',
//                            'admin.shop.health'
                        ]
                    ]),
            ],
//            'query' => [
//                'shop_id' => $shop->id,
//            ],
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
        $this->assertIsString($json['publicKey']);
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
                            'shop_' . 'invalid_uid',
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
    public function itShouldFailWithInvalidShopId()
    {
        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        $response = $this->client->get('/module/ps_accounts/apiV2ShopHealthCheck', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->makeBearer([
                        'aud' => [
                            'shop_' . $shop->uuid,
                        ],
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

    /**
     * @param array $data
     *
     * @return string
     */
    public function makeBearer(array $data)
    {
//        if (!isset($data['iat'])) {
//            $data['iat'] = time();
//        }
//        if (!isset($data['exp'])) {
//            $data['exp'] = time() + 3600;
//        }
        return JWT::encode($data, $this->privateKey, 'RS256', 'public:hydra.jwt.access-token');
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function writeJwks()
    {
        if (! isset($this->cachedJwks)) {
            $this->cachedJwks = new CachedFile(_PS_CACHE_DIR_ . 'ps_accounts' . '/jwks.json');
        }
        $this->cachedJwks->write($this->jwks);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function writeWellKnown()
    {
        if (! isset($this->cachedWellKnown)) {
            $this->cachedWellKnown = new CachedFile(_PS_CACHE_DIR_ . 'ps_accounts' . '/openid-configuration.json');
        }
        $this->cachedWellKnown->write($this->wellKnown);
    }
}
