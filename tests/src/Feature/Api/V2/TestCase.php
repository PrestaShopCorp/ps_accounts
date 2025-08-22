<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v2;

use PrestaShop\Module\PsAccounts\Service\OAuth2\CachedFile;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\JWT;

class TestCase extends \PrestaShop\Module\PsAccounts\Tests\Feature\TestCase
{
    /**
     * @var string
     */
    protected $wellKnown = <<<JSON
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
    protected $privateKey = <<<EOD
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
    protected $jwks = <<<JSON
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
    protected $cachedJwks;

    /**
     * @var CachedFile
     */
    protected $cachedWellKnown;

    public function set_up()
    {
        // useless to write wellknown cause JWKS is already present
        //$this->writeWellKnown();

        $this->writeJwks();

        parent::set_up();
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function makeBearer(array $data)
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
    protected function writeJwks()
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
    protected function writeWellKnown()
    {
        if (! isset($this->cachedWellKnown)) {
            $this->cachedWellKnown = new CachedFile(_PS_CACHE_DIR_ . 'ps_accounts' . '/openid-configuration.json');
        }
        $this->cachedWellKnown->write($this->wellKnown);
    }
}
