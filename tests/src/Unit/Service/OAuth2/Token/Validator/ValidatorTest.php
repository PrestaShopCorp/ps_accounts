<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\OAuth2\Token\Validator;

use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\AudienceInvalidException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\KidInvalidException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\ScopeInvalidException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\SignatureInvalidException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\TokenExpiredException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\TokenInvalidException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Validator;
use PrestaShop\Module\PsAccounts\Tests\Unit\Service\OAuth2\TestCase;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\JWT;

class ValidatorTest extends TestCase
{
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

    private $privateKey2 = <<<EOD
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCskCC5Pq47mE/R
5dNhhduJocvVpJrAsUUbXJjnevyLeq6LbBXT6O10ja6gp7q9VX4dHCiIZQIDkNF9
Ceke/Zu7WnKnf2HKW56NgneXGJklv/nmTr3jNy7vq56scUayqVHGsxY8fh/mLSGc
ndxbIobPwpie2ZfjXxYEIUAG1vRXnLfeiBAozslA4DfQOil+8/W8pkWgbUSewf3R
XXwMev9s8g+zo3nLz+VdldIsFXwTwCPUEmbv1bskFSAikRIi6cHsZecfx7j4VprJ
7fdWgCJmeGKG6Ev1HxsiyThfBqlQIJoZMdWiqK0Gr8meMtjDMQeJ+Oj4rsCz7Bn/
mcufYFIXAgMBAAECggEADusFg947rnfpa/TuxD86jnhyqGKVj6kDl2z25F7JLGm2
Yzk8iIME3Dmkv3YgpQjvYSbeXi7qm1JOufznBBXapmTDBWCex0wKYKNxy+8wIN0A
MVKHWfhFp39HtWgJkPJ7A67XHPMDtktv3MaiQS1dSDgY0krEtb8Hpo2//Oy3rPHW
ynUXaCnL3C8K+lXf04jeAPebBVAYlWbLjnPs6vOVxVPFGujrAiNfM68HovTKuXLN
VW6+lvPIQcbJD4no/Ikr6FTIp4cWRTWvD5AQ1WMrP7viwOkI63NCQUMrZQCmegxf
4+bQ7riTAxpv4rshVCvU8i2+j8kKg4RM74MFLPi84QKBgQDQMP4M7y3FRXAK9B3z
vvhOcqA82In6qTnOq4q6j3f0VleZv62+WwB1b39CzP/IK8gnfma2n6E9btZEeXvj
U5q3sSI5k35lnfHlYj7yQj4LW5CQ+SnGv0zgALzBz/5wzKV8EyxVzreTtpG68wmi
oF9hR/w+uDhr2hf2vyBgV3UcRwKBgQDUMKO6ir2tLwsPaZ6XA3jpGanrOAPqsBTY
YeWUuy2TDWBZXZ15wUgcCPo64IyJhv6AiKKbC42V12NUSc4dNoC85fChKx8WVzet
eLZlqbpq5O3xDnRChrVXYT4XVQ72MkqL+ZQzBSuBGeQOWD8u99s5JuPqa37rBM61
zvzvWv2TsQKBgQCilwBxPYGzZK1CALA7odLuARsfKcVoWyzoOJQZat41lhDH5Che
V1eeXzs2Aj9oJrkkDtVMnpIgFiWESJP2T16vQskFyiiWV6mIiRe7vvwRhqr6bXyw
2MnCzxCbFEMT+N7sbVTclppL4/Mf25qUxUZ4BO38VYbKxAKfE1jHpMWzFQKBgCbf
Oj9kR1dAtP//02PK6Q2a+/vxaRZHZ6o0VQCoQbMc0jfM5Jp3hCROqLi8hkJzjpAx
d8h7l1aJ/NJmKvJDF9aDMU/1PF61X6fR42hAbbxDcCunADlnwDTfxaY0mOcVU37N
HA80tUGTYqoWFI0gLvMLYtmBE+EuIkhAQSoAO1AhAoGBAKXq1q81lkRrHsnFTWZb
ej6m2Q5STAfX6y/7yKEq0bMUweLeIjP5CEPAtU6lk0U2edwQVOfHuxSKZEBE4mkP
OhgrplYHTcBJNFzfQv9a0PyvqZ2ajrl7bLkUHj1+PBwKno77ranK7K2rv9xU442p
E/ipfpAtIOcKEIBYzwYt7lD9
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
    private $jwks2 = <<<JSON
{
  "keys": [
    {
      "kty": "RSA",
      "n": "rJAguT6uO5hP0eXTYYXbiaHL1aSawLFFG1yY53r8i3qui2wV0-jtdI2uoKe6vVV-HRwoiGUCA5DRfQnpHv2bu1pyp39hyluejYJ3lxiZJb_55k694zcu76uerHFGsqlRxrMWPH4f5i0hnJ3cWyKGz8KYntmX418WBCFABtb0V5y33ogQKM7JQOA30DopfvP1vKZFoG1EnsH90V18DHr_bPIPs6N5y8_lXZXSLBV8E8Aj1BJm79W7JBUgIpESIunB7GXnH8e4-Faaye33VoAiZnhihuhL9R8bIsk4XwapUCCaGTHVoqitBq_JnjLYwzEHifjo-K7As-wZ_5nLn2BSFw",
      "e": "AQAB",
      "ext": true,
      "kid": "public:hydra.jwt.access-token2",
      "alg": "RS256",
      "use": "sig"
    }
  ]
}

JSON;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @inject
     *
     * @var OAuth2Service
     */
    protected $oAuth2Service;

    /**
     * @inject
     *
     * @var OAuth2Client
     */
    protected $oAuth2Client;

    /**
     * @inject
     *
     * @var Link
     */
    protected $link;

    /**
     * @var int
     */
    private $leeway;

    /**
     * @return void
     */
    protected function set_up()
    {
        parent::set_up();

        $this->oAuth2Service = new OAuth2Service(
            [ClientConfig::BASE_URI => 'https://oauth.test.fr',],
            $this->oAuth2Client,
            $this->getTestCacheDir()
        );

        $this->oAuth2Service->setHttpClient($this->createMockedHttpClient());

        $this->wellKnownResponse = $this->createResponse($this->wellKnown);
        $this->jwksResponse = $this->createResponse($this->jwks);

        $this->oAuth2Service->clearCache();

        $this->leeway = (int) $this->module->getParameter('ps_accounts.token_validator_leeway');

        $this->validator = new Validator($this->oAuth2Service, $this->leeway);
    }

    /**
     * @test
     */
    public function itShouldStoreCachedJwks()
    {
        $filename = $this->oAuth2Service->getCachedJwks()->getFilename();

        $this->assertFalse(file_exists($filename));

        $this->oAuth2Service->getJwks();

        $this->assertTrue(file_exists($filename));
    }

    /**
     * @test
     */
    public function itShouldVerifyValidSignature()
    {
        $token = $this->validator->verifyToken(JWT::encode([
            'aud' => [
                'https://mashop.net',
            ],
        ], $this->privateKey, 'RS256', 'public:hydra.jwt.access-token'));

        $this->assertEquals('https://mashop.net', $token->aud[0]);
    }

    /**
     * @test
     */
    public function itShouldNotVerifyInvalidSignature()
    {
        $this->expectException(SignatureInvalidException::class);

        $this->validator->verifyToken(JWT::encode([
            'aud' => [
                'https://mashop.net',
            ],
        ], $this->privateKey2, 'RS256', 'public:hydra.jwt.access-token'));
    }

    /**
     * @test
     */
    public function itShouldRefreshJwksOnInvalidKid()
    {
        // cache jwks version 1
        $this->jwksResponse = $this->createResponse($this->jwks);
        $this->oAuth2Service->getJwks();

        // rotate to jwks version 2
        $this->jwksResponse = $this->createResponse($this->jwks2);

        $token = $this->validator->verifyToken(JWT::encode([
            'aud' => [
                'https://mashop.net',
            ],
        ], $this->privateKey2, 'RS256', 'public:hydra.jwt.access-token2'));

        $this->assertEquals('https://mashop.net', $token->aud[0]);
    }

    /**
     * @test
     */
    public function itShouldFailOnInvalidKid()
    {
        $this->expectException(KidInvalidException::class);

        $this->validator->verifyToken(JWT::encode([
            'aud' => [
                'https://mashop.net',
            ],
        ], $this->privateKey2, 'RS256', 'naughty-kid'));
    }

    /**
     * @test
     */
    public function itShouldValidateToken()
    {
        $jwtString = $this->encodeToken([
            'aud' => [
                'https://mashop.net',
            ],
            'scp' => [
                'entity.read',
                'entity.write',
                'entity.delete',
            ],
        ]);

        $token = $this->validator->validateToken($jwtString, [
            'entity.read',
            'entity.write',
        ], [
            'https://mashop.net',
        ]);

        $this->assertEquals('https://mashop.net', $token->aud[0]);

        $token = $this->validator->validateToken($jwtString, [
        ], [
            'https://mashop.net',
        ]);

        $this->assertEquals('https://mashop.net', $token->aud[0]);

        $token = $this->validator->validateToken($jwtString, [
            'entity.read',
            'entity.write',
        ]);

        $this->assertEquals('https://mashop.net', $token->aud[0]);
    }

    /**
     * @test
     */
    public function itShouldNotValidateTokenWithInvalidAudience()
    {
        $this->expectException(AudienceInvalidException::class);

        $jwtString = $this->encodeToken([
            'aud' => [
                'https://mashop.net',
            ],
            'scp' => [
                'entity.read',
                'entity.write',
                'entity.delete',
            ],
        ]);

        $this->validator->validateToken($jwtString, [
            'entity.read',
            'entity.write',
        ], [
            'https://shopifees.net',
        ]);
    }

    /**
     * @test
     */
    public function itShouldNotValidateTokenWithoutAudience()
    {
        $this->expectException(AudienceInvalidException::class);

        $jwtString = $this->encodeToken([
//            'aud' => [
//                'https://mashop.net',
//            ],
            'scp' => [
                'entity.read',
                'entity.write',
                'entity.delete',
            ],
        ]);

        $this->validator->validateToken($jwtString, [
            'entity.read',
            'entity.write',
        ], [
            'https://shopifees.net',
        ]);
    }

    /**
     * @test
     */
    public function itShouldNotValidateTokenWithInvalidScope()
    {
        $this->expectException(ScopeInvalidException::class);

        $jwtString = $this->encodeToken([
            'aud' => [
                'https://mashop.net',
            ],
            'scp' => [
                'entity.red',
                'entity.write',
                'entity.delete',
            ],
        ]);

        $this->validator->validateToken($jwtString, [
            'entity.read',
            'entity.write',
        ], [
            'https://mashop.net',
        ]);
    }

    /**
     * @test
     */
    public function itShouldNotValidateTokenWithoutScope()
    {
        $this->expectException(ScopeInvalidException::class);

        $jwtString = $this->encodeToken([
            'aud' => [
                'https://mashop.net',
            ],
//            'scp' => [
//                'entity.red',
//                'entity.write',
//                'entity.delete',
//            ],
        ]);

        $this->validator->validateToken($jwtString, [
            'entity.read',
            'entity.write',
        ], [
            'https://mashop.net',
        ]);
    }

    /**
     * @test
     */
    public function itShouldValidateNotExpiredToken()
    {
        $jwtString = $this->encodeToken([
            'iat' => time(),
            'exp' => time() + 3600,
        ]);

        $this->assertTrue(is_object($this->validator->validateToken($jwtString)));
    }

    /**
     * @test
     */
    public function itShouldNotValidateExpiredToken()
    {
        $this->expectException(TokenExpiredException::class);

        $jwtString = $this->encodeToken([
            'iat' => time() - 3600,
            'exp' => time() - $this->leeway,
        ]);

        $this->validator->validateToken($jwtString);
    }

    /**
     * @test
     */
    public function itShouldNotValidateTokenNotYetValid()
    {
        $this->expectException(TokenInvalidException::class);

        $jwtString = $this->encodeToken([
            'iat' => time() + 3600,
            'exp' => time() + 3600*2,
        ]);

        $this->validator->validateToken($jwtString);
    }

    /**
     * @test
     */
    public function itShouldValidateTokenAccordingToLeeway()
    {
        $jwtString = $this->encodeToken([
            'iat' => time() + $this->leeway - 1,
            'exp' => time() + $this->leeway + 3600,
        ]);

        $this->assertIsObject($this->validator->validateToken($jwtString));
    }

    /**
     * @param array $payload
     * @param string $privateKey
     * @param string $kid
     *
     * @return string
     */
    private function encodeToken(array $payload, $privateKey = null, $kid = 'public:hydra.jwt.access-token')
    {
        if ($privateKey === null) {
            $privateKey = $this->privateKey;
        }

        return JWT::encode($payload, $privateKey, 'RS256', $kid);
    }
}
