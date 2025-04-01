<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\OAuth2;

use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\UserInfo;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\WellKnown;

class OAuth2ServiceTest extends TestCase
{
    /**
     * @inject
     *
     * @var Link
     */
    protected $link;

    /**
     * @inject
     *
     * @var OAuth2Client
     */
    protected $oAuth2Client;

    /**
     * @var string
     */
    private $wellKnown = <<<JSON
{
    "authorization_endpoint": "https://oauth.foo.bar/oauth2/auth",
    "token_endpoint": "https://oauth.foo.bar/oauth2/token",
    "userinfo_endpoint": "https://oauth.foo.bar/userinfo",
    "jwks_uri": "https://oauth.foo.bar/.well-known/jwks.json",
    "end_session_endpoint":"https://oauth.foo.bar/oauth2/sessions/logout"
}
JSON;

    /**
     * @return void
     */
    protected function set_up()
    {
        parent::set_up();

//        $this->apiClient = new PrestaShop([
//            'clientId' => 'test-client',
//            'clientSecret' => 'secret',
//            'redirectUri' => 'https://test-client-redirect.net',
//            'cachedWellKnown' => $this->cachedOpenIdConfiguration,
//            'uiLocales' => ['fr-CA', 'en'],
//            'acrValues' => ['prompt:login'],
//        ]);

        $this->oAuth2Client->update(
            $this->faker->uuid,
            $this->faker->password
        );

        $this->oAuth2Service = new OAuth2Service(
            [ClientConfig::BASE_URI => 'https://oauth.test.fr',],
            $this->oAuth2Client,
            $this->link,
            $this->getTestCacheDir()
        );

        $this->oAuth2Service->setHttpClient($this->createMockedHttpClient());

        $this->wellKnownResponse = $this->createResponse($this->wellKnown);

        $this->oAuth2Service->clearCache();
    }

    /**
     * @test
     */
    public function itShouldStoreCachedOpenIdConfiguration()
    {
        $filename = $this->oAuth2Service->getCachedWellKnown()->getFilename();

        $this->assertFalse(file_exists($filename));

        $this->assertInstanceOf(WellKnown::class, $this->oAuth2Service->getWellKnown());

        $this->assertTrue(file_exists($filename));
    }

    /**
     * @test
     */
    public function itShouldRefreshCachedOpenIdConfiguration()
    {
        $this->oAuth2Service->getCachedWellKnown()->setTtl(1);

        $openIdConfiguration = $this->oAuth2Service->getWellKnown();

        $this->assertFalse($this->oAuth2Service->getCachedWellKnown()->isExpired());
        $this->assertInstanceOf(WellKnown::class, $openIdConfiguration);
        $this->assertEquals('https://oauth.foo.bar/oauth2/auth', $openIdConfiguration->authorization_endpoint);

        usleep(2000000);

        $this->assertTrue($this->oAuth2Service->getCachedWellKnown()->isExpired());

        $this->wellKnownResponse = $this->createResponse(<<<JSON
{
    "authorization_endpoint": "https://oauth-refreshed.foo.bar/oauth2/auth",
    "token_endpoint": "https://oauth-refreshed.foo.bar/oauth2/token",
    "userinfo_endpoint": "https://oauth-refreshed.foo.bar/userinfo",
    "jwks_uri": "https://oauth-refreshed.prestashop.com/.well-known/jwks.json"
}
JSON
        );

        $openIdConfiguration = $this->oAuth2Service->getWellKnown();

        $this->assertInstanceOf(WellKnown::class, $openIdConfiguration);
        $this->assertEquals('https://oauth-refreshed.foo.bar/oauth2/auth', $openIdConfiguration->authorization_endpoint);
    }

    /**
     * @test
     */
    public function itShouldGenerateAuthorizationUrl()
    {
        $url = $this->oAuth2Service->getAuthorizationUri(
            'not-a-random-state',
            null,
            '',
            'fr-CA en',
            'prompt:login'
        );
        $uri = parse_url($url);
        $query = [];

        if (\is_array($uri) && isset($uri['query'])) {
            parse_str($uri['query'], $query);
        }

        $this->assertEquals('openid offline_access', $query['scope']);
        $this->assertEquals($this->oAuth2Client->getClientId(), $query['client_id']);
        $this->assertEquals($this->oAuth2Client->getRedirectUri(), $query['redirect_uri']);
        $this->assertEquals('fr-CA en', $query['ui_locales']);
        $this->assertEquals('prompt:login', $query['acr_values']);
        $this->assertArrayHasKey('response_type', $query);
    }

    /**
     * @test
     */
    public function itShouldGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->oAuth2Service->getWellKnown()->token_endpoint;
        $uri = parse_url($url);

        $path = '';
        if (\is_array($uri) && isset($uri['path'])) {
            $path = $uri['path'];
        }

        $this->assertEquals('/oauth2/token', $path);
    }

    /**
     * @test
     */
    public function itShouldGetAuthorizationUrl()
    {
        $url = $this->oAuth2Service->getWellKnown()->authorization_endpoint;
        $uri = parse_url($url);

        $path = '';
        if (\is_array($uri) && isset($uri['path'])) {
            $path = $uri['path'];
        }

        $this->assertEquals('/oauth2/auth', $path);
    }

    /**
     * @test
     */
    public function itShouldGetAccessTokenWithAuthorizationCode()
    {
        $this->accessTokenResponse = $this->createResponse(<<<JSON
{
  "access_token": "mock_access_token",
  "token_type": "bearer",
  "refresh_token": "mock_refresh_token",
  "expires_in": 7200,
  "scope": "public",
  "created_at": 1613125557
}
JSON
        );

        $token = $this->oAuth2Service->getAccessTokenByAuthorizationCode(
            $this->faker->sha256
        );

        $this->assertEquals('mock_access_token', $token->access_token);
        $this->assertEquals('mock_refresh_token', $token->refresh_token);
        $this->assertLessThanOrEqual(time() + 7200, $token->expires);
        $this->assertGreaterThanOrEqual(time(), $token->expires);
    }

    /**
     * @test
     */
    public function itShouldGetAccessTokenWithClientCredentials()
    {
        $this->accessTokenResponse = $this->createResponse(<<<JSON
{
  "access_token": "mock_access_token",
  "token_type": "bearer",
  "expires_in": 7200,
  "scope": "public",
  "created_at": 1613125557
}
JSON
        );

        $token = $this->oAuth2Service->getAccessTokenByClientCredentials(
            [ 'scope1' ],
            [ $this->faker->url ]
        );

        $this->assertEquals('mock_access_token', $token->access_token);
        $this->assertNull($token->refresh_token);
        $this->assertLessThanOrEqual(time() + 7200, $token->expires);
        $this->assertGreaterThanOrEqual(time(), $token->expires);
    }

    /**
     * @test
     */
    public function itShouldGetResourceOwner()
    {
        $this->resourceOwnerResponse = $this->createResponse(<<<JSON
{
  "sub": "4rFN5bm2piPeHTYUFtUIwcyFKKKOp",
  "email": "john.doe@prestashop.com",
  "email_verified": "1",
  "name": "John Doe",
  "picture": "https://lh3.googleusercontent.com/a/AATXAJzK3D_K4_7YHFDQHFD3C_1ViDfRVDmQTukCyw=s96-c"
}
JSON
        );

        $resourceOwner = $this->oAuth2Service->getUserInfo('mock_access_token');
        $this->assertInstanceOf(UserInfo::class, $resourceOwner);
        $this->assertArraySubset([
            'sub' => '4rFN5bm2piPeHTYUFtUIwcyFKKKOp',
            'email' => 'john.doe@prestashop.com',
            'email_verified' => true,
            'name' => 'John Doe',
            'picture' => 'https://lh3.googleusercontent.com/a/AATXAJzK3D_K4_7YHFDQHFD3C_1ViDfRVDmQTukCyw=s96-c',
        ], $resourceOwner->toArray());
    }

    /**
     * @test
     */
    public function itShouldGenerateLogoutUrl()
    {
        $postLogoutRedirectUri = $this->faker->url;
        $idToken = 'someRandomIdToken';

        $url = $this->oAuth2Service->getLogoutUri(
            $postLogoutRedirectUri,
            $idToken
        );
        $uri = parse_url($url);
        $path = '';
        $query = [];

        if (\is_array($uri) && isset($uri['query'])) {
            parse_str($uri['query'], $query);
        }

        if (\is_array($uri) && isset($uri['path'])) {
            $path = $uri['path'];
        }

        $this->assertEquals('/oauth2/sessions/logout', $path);
        $this->assertEquals($idToken, $query['id_token_hint']);
        $this->assertEquals($postLogoutRedirectUri, $query['post_logout_redirect_uri']);
        // $this->assertEquals('fr-CA en', $query['ui_locales']);
    }

    /**
     * @test
     */
    public function itShouldGetBaseSessionLogoutUrl()
    {
        $url = $this->oAuth2Service->getWellKnown()->end_session_endpoint;
        $uri = parse_url($url);

        $path = '';
        if (\is_array($uri) && isset($uri['path'])) {
            $path = $uri['path'];
        }

        $this->assertEquals('/oauth2/sessions/logout', $path);
    }

    /**
     * @test
     */
    public function itShouldHandleErrors()
    {
        $this->accessTokenResponse = $this->createResponse(<<<JSON
{
  "error_description": "This is the description",
  "error": "error_name"
}
JSON
            , 403);

        $this->expectException(OAuth2Exception::class);
        $this->expectExceptionMessage('403 - error_name: This is the description');
        $this->oAuth2Service->getAccessTokenByAuthorizationCode($this->faker->sha256);
    }

    /**
     * @test
     */
    public function itShouldHandleEmptyErrors()
    {
        $this->accessTokenResponse = $this->createResponse('{}', 403);

        $this->expectException(OAuth2Exception::class);
        $this->expectExceptionMessage('403 - Unable to get access token');
        $this->oAuth2Service->getAccessTokenByAuthorizationCode($this->faker->sha256);
    }
}
