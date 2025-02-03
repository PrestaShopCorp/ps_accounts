<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\OAuth2;

use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2LogoutTrait;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client as HttpClient;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Http\Client\Options;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\OAuth2\Response\AccessToken;
use PrestaShop\Module\PsAccounts\OAuth2\Response\UserInfo;
use PrestaShop\Module\PsAccounts\OAuth2\Response\WellKnown;
use PrestaShop\Module\PsAccounts\Vendor\Ramsey\Uuid\Uuid;

class ApiClient
{
    /**
     * openid-configuration cache (24 Hours)
     */
    const OPENID_CONFIGURATION_CACHE_TTL = 60 * 60 * 24;
    const OPENID_CONFIGURATION_JSON = 'openid-configuration.json';
    const JWKS_JSON = 'jwks.json';

    /**
     * @var string
     */
    private $baseUri;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var WellKnown
     */
    private $wellKnown;

    /**
     * @var CachedFile
     */
    private $cachedWellKnown;

    /**
     * @var CachedFile
     */
    private $cachedJwks;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Link
     */
    private $link;

    /**
     * @var int
     */
    private $defaultTimeout;

    /**
     * @var bool
     */
    protected $sslCheck;

    /**
     * @param string $baseUri
     * @param Client $client
     * @param Link $link
     * @param string $cacheDir
     * @param int $defaultTimeout
     * @param bool $sslCheck
     *
     * @throws \Exception
     */
    public function __construct(
        $baseUri,
        Client $client,
        Link $link,
        $cacheDir,
        $defaultTimeout = 20,
        $sslCheck = true
    ) {
        $this->baseUri = $baseUri;
        $this->client = $client;
        $this->link = $link;
        $this->defaultTimeout = $defaultTimeout;
        $this->sslCheck = $sslCheck;

        $this->cachedWellKnown = new CachedFile(
            $cacheDir . '/' . self::OPENID_CONFIGURATION_JSON,
            self::OPENID_CONFIGURATION_CACHE_TTL
        );
        $this->cachedJwks = new CachedFile(
            $cacheDir . '/' . self::JWKS_JSON
        );
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = (new Factory())->create([
                'name' => static::class,
                'baseUri' => $this->baseUri,
                'headers' => $this->getHeaders(),
                'timeout' => $this->defaultTimeout,
                'sslCheck' => $this->sslCheck,
            ]);
        }

        return $this->httpClient;
    }

    /**
     * @param HttpClient $httpClient
     *
     * @return void
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param array $additionalHeaders
     *
     * @return array
     */
    private function getHeaders($additionalHeaders = [])
    {
        return array_merge([
            'Accept' => 'application/json',
            'X-Module-Version' => \Ps_accounts::VERSION,
            'X-Prestashop-Version' => _PS_VERSION_,
            'X-Request-ID' => Uuid::uuid4()->toString(),
        ], $additionalHeaders);
    }

    /**
     * @return string
     */
    public function getOpenIdConfigurationUri()
    {
        return \preg_replace('/\\/?$/', '/.well-known/openid-configuration', $this->baseUri);
    }

    /**
     * @return WellKnown
     *
     * @throws OAuth2Exception
     */
    public function getWellKnown()
    {
        /* @phpstan-ignore-next-line */
        if (!isset($this->wellKnown) || $this->cachedWellKnown->isExpired()) {
            $this->wellKnown = new WellKnown(json_decode($this->getWellKnownFromCache(), true));
        }

        return $this->wellKnown;
    }

    /**
     * @param bool $forceRefresh
     *
     * @return string
     *
     * @throws OAuth2Exception
     */
    protected function getWellKnownFromCache($forceRefresh = false)
    {
        if ($this->cachedWellKnown->isExpired() || $forceRefresh) {
            $this->cachedWellKnown->write(
                json_encode($this->fetchWellKnown(), JSON_UNESCAPED_SLASHES)
            );
        }

        return (string) $this->cachedWellKnown->read();
    }

    /**
     * @return array
     *
     * @throws OAuth2Exception
     */
    protected function fetchWellKnown()
    {
        $response = $this->getHttpClient()->get($this->getOpenIdConfigurationUri());

        if (!$response->isValid()) {
            throw new OAuth2Exception($this->getResponseErrorMsg($response, 'Unable to get openid-configuration'));
        }

        return $response->getBody();
    }

    /**
     * @param bool $forceRefresh
     *
     * @return array
     *
     * @throws OAuth2Exception
     */
    public function getJwks($forceRefresh = false)
    {
        if ($this->cachedJwks->isExpired() || $forceRefresh) {
            $this->cachedJwks->write(
                json_encode(
                    $this->getHttpClient()->get($this->getWellKnown()->jwks_uri)
                        ->getBody(),  JSON_UNESCAPED_SLASHES
                )
            );
        }

        return json_decode($this->cachedJwks->read(), true);
    }

    /**
     * @param array $scope
     * @param array $audience
     *
     * @return AccessToken access token
     *
     * @throws OAuth2Exception
     */
    public function getAccessTokenByClientCredentials(array $scope = [], array $audience = [])
    {
        $this->assertClientExists();

        $response = $this->getHttpClient()->post(
            $this->getWellKnown()->token_endpoint,
            [
                Options::REQ_FORM => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->client->getClientId(),
                    'client_secret' => $this->client->getClientSecret(),
                    'scope' => implode(' ', $scope),
                    'audience' => implode(' ', $audience),
                ],
            ]
        );

        if (!$response->isValid()) {
            throw new OAuth2Exception($this->getResponseErrorMsg($response, 'Unable to get access token'));
        }

        return new AccessToken($response->getBody());
    }

    /**
     * @param string $state
     * @param string $redirectUri
     * @param string|null $pkceCode
     * @param string $pkceMethod
     * @param string $uiLocales
     * @param string $acrValues
     *
     * @return string authorization flow uri
     *
     * @throws OAuth2Exception
     */
    public function getAuthorizationUri(
        $state,
        $redirectUri,
        $pkceCode = null,
        $pkceMethod = 'S256',
        $uiLocales = 'fr',
        $acrValues = 'prompt:login'
    ) {
        $this->assertClientExists();

        return $this->getWellKnown()->authorization_endpoint . '?' .
            http_build_query(array_merge([
                'ui_locales' => $uiLocales,
                'state' => $state,
                'scope' => 'openid offline_access',
                'response_type' => 'code',
                'approval_prompt' => 'auto',
                'redirect_uri' => $redirectUri,
                'client_id' => $this->client->getClientId(),
                'acr_values' => $acrValues,
            ], $pkceCode ? [
                'code_challenge' => trim(strtr(base64_encode(hash('sha256', $pkceCode, true)), '+/', '-_'), '='),
                'code_challenge_method' => $pkceMethod,
            ] : []));
    }

    /**
     * @example  http://my-shop.mydomain/admin-path/index.php?controller=AdminOAuth2PsAccounts
     *
     * @return string
     */
    public function getAuthRedirectUri()
    {
        return $this->link->getAdminLink('AdminOAuth2PsAccounts', false);
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public function getRandomState($length = 32)
    {
        /* @phpstan-ignore-next-line */
        return bin2hex(random_bytes((int) ($length / 2)));
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public function getRandomPkceCode($length = 64)
    {
        /* @phpstan-ignore-next-line */
        return (string) substr(strtr(base64_encode(random_bytes($length)), '+/', '-_'), 0, $length);
    }

    /**
     * @param string $code
     * @param string|null $pkceCode
     * @param string|null $redirectUri
     * @param array $scope
     * @param array $audience
     *
     * @return AccessToken access token
     *
     * @throws OAuth2Exception
     */
    public function getAccessTokenByAuthorizationCode(
        $code,
        $pkceCode = null,
        $redirectUri = null,
        array $scope = [],
        array $audience = []
    ) {
        $this->assertClientExists();

        $response = $this->getHttpClient()->post(
            $this->getWellKnown()->token_endpoint,
            [
                Options::REQ_FORM => array_merge([
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->client->getClientId(),
                    'client_secret' => $this->client->getClientSecret(),
                    'code' => $code,
                    'scope' => implode(' ', $scope),
                    'audience' => implode(' ', $audience),
                ], $pkceCode ? [
                    'code_verifier' => $pkceCode,
                    'redirect_uri' => $redirectUri,
                ] : []),
            ]
        );

        if (!$response->isValid()) {
            throw new OAuth2Exception($this->getResponseErrorMsg($response, 'Unable to get access token'));
        }

        return new AccessToken($response->getBody());
    }

    /**
     * @param string $refreshToken
     *
     * @return AccessToken
     *
     * @throws OAuth2Exception
     */
    public function refreshAccessToken($refreshToken)
    {
        $this->assertClientExists();

        $response = $this->getHttpClient()->post(
            $this->getWellKnown()->token_endpoint,
            [
                Options::REQ_FORM => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->client->getClientId(),
                    'refresh_token' => $refreshToken,
                ],
            ]
        );

        if (!$response->isValid()) {
            throw new OAuth2Exception($this->getResponseErrorMsg($response, 'Unable to refresh access token'));
        }

        return new AccessToken($response->getBody());
    }

    /**
     * @param string $accessToken
     *
     * @return UserInfo
     */
    public function getUserInfo($accessToken)
    {
        $response = $this->getHttpClient()->get(
            $this->getWellKnown()->userinfo_endpoint,
            [
                Options::REQ_HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ]),
            ]
        );

        if (!$response->isValid()) {
            throw new OAuth2Exception($this->getResponseErrorMsg($response, 'Unable to get user infos'));
        }

        return new UserInfo($response->getBody());
    }

    /**
     * @param string $postLogoutRedirectUri
     * @param string|null $idTokenHint
     *
     * @return string
     */
    public function getLogoutUri($postLogoutRedirectUri, $idTokenHint = null)
    {
        return $this->getWellKnown()->end_session_endpoint . '?' .
            http_build_query([
                'id_token_hint' => $idTokenHint,
                'post_logout_redirect_uri' => $postLogoutRedirectUri,
            ]);
    }

    /**
     * @example http://my-shop.mydomain/admin-path/index.php?controller=AdminLogin&logout=1&oauth2Callback=1
     *
     * @return string
     */
    public function getPostLogoutRedirectUri()
    {
        return $this->link->getAdminLink('AdminLogin', false, [], [
            'logout' => 1,
            OAuth2LogoutTrait::getQueryLogoutCallbackParam() => 1,
        ]);
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $this->cachedJwks->clear();
        $this->cachedWellKnown->clear();
    }

    /**
     * @return CachedFile
     */
    public function getCachedWellKnown()
    {
        return $this->cachedWellKnown;
    }

    /**
     * @return CachedFile
     */
    public function getCachedJwks()
    {
        return $this->cachedJwks;
    }

    /**
     * @return void
     *
     * @throws OAuth2Exception
     */
    protected function assertClientExists()
    {
        if (!$this->client->exists()) {
            throw new OAuth2Exception('OAuth2 client not configured');
        }
    }

    /**
     * @param Response $response
     * @param string $defaultMessage
     *
     * @return string
     */
    protected function getResponseErrorMsg(Response $response, $defaultMessage = '')
    {
        $msg = $defaultMessage;
        $body = $response->getBody();
        if (isset($body['error']) &&
            isset($body['error_description'])
        ) {
            $msg = $body['error'] . ': ' . $body['error_description'];
        }

        return $response->getStatusCode() . ' - ' . $msg;
    }
}
