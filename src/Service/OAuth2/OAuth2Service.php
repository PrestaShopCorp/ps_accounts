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

namespace PrestaShop\Module\PsAccounts\Service\OAuth2;

use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client as HttpClient;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\UserInfo;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\WellKnown;
use PrestaShop\Module\PsAccounts\Vendor\Ramsey\Uuid\Uuid;

class OAuth2Service
{
    /**
     * cached openid-configuration ttl (24 Hours)
     */
    const OPENID_CONFIGURATION_CACHE_TTL = 60 * 60 * 24;

    /**
     * cached openid-configuration filename
     */
    const OPENID_CONFIGURATION_JSON = 'openid-configuration.json';

    /**
     * cached JWKS (JSON Web Key Set) filename
     */
    const JWKS_JSON = 'jwks.json';

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var array
     */
    protected $clientConfig;

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
     * @var OAuth2Client
     */
    private $oAuth2Client;

    /**
     * @var string[]
     */
    protected $defaultScopes = [
        'openid',
        'offline_access',
    ];

    /**
     * @param array $config
     * @param OAuth2Client $oAuth2Client
     * @param string $cacheDir
     *
     * @throws \Exception
     */
    public function __construct(
        array $config,
        OAuth2Client $oAuth2Client,
        $cacheDir
    ) {
        $this->clientConfig = array_merge([
            ClientConfig::NAME => static::class,
            ClientConfig::HEADERS => $this->getHeaders(),
        ], $config);

        $this->oAuth2Client = $oAuth2Client;

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
            $this->httpClient = (new Factory())->create($this->clientConfig);
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
            $wellKnown = $this->fetchWellKnown();

            $this->cachedWellKnown->write(
                json_encode($wellKnown, JSON_UNESCAPED_SLASHES)
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
        //$response = $this->getHttpClient()->get($this->getOpenIdConfigurationUri());
        $response = $this->getHttpClient()->get('/.well-known/openid-configuration');

        if (!$response->isSuccessful) {
            throw new OAuth2ServerException($response, 'Unable to get openid-configuration');
        }

        return $response->body;
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
            $response = $this->getHttpClient()->get($this->getWellKnown()->jwks_uri);

            if (!$response->isSuccessful) {
                throw new OAuth2ServerException($response, 'Unable to get JWKS');
            }

            $this->cachedJwks->write(
                json_encode($response->body, JSON_UNESCAPED_SLASHES)
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
                Request::FORM => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->oAuth2Client->getClientId(),
                    'client_secret' => $this->oAuth2Client->getClientSecret(),
                    'scope' => implode(' ', $scope),
                    'audience' => implode(' ', $audience),
                    //'redirect_uri' => $this->getOAuth2Client()->getRedirectUri(),
                ],
            ]
        );

        if (!$response->isSuccessful) {
            throw new OAuth2ServerException($response, 'Unable to get access token');
        }

        return new AccessToken($response->body);
    }

    /**
     * @param string $state
     * @param string|null $pkceCode
     * @param string $pkceMethod
     * @param string $uiLocales
     * @param string $acrValues
     * @param string $prompt
     * @param int|null $shopId
     *
     * @return string authorization flow uri
     *
     * @throws OAuth2Exception
     */
    public function getAuthorizationUri(
        $state,
        $pkceCode = null,
        $pkceMethod = 'S256',
        $uiLocales = 'fr',
        $acrValues = 'prompt:login',
        $prompt = 'none',
        $shopId = null
    ) {
        $this->assertClientExists();

        return $this->getWellKnown()->authorization_endpoint . '?' .
            http_build_query(array_merge([
                'ui_locales' => $uiLocales,
                'state' => $state,
                'scope' => implode(' ', $this->defaultScopes),
                'response_type' => 'code',
                'approval_prompt' => 'auto',
                'redirect_uri' => $this->getOAuth2Client()->getRedirectUri([], $shopId),
                'client_id' => $this->oAuth2Client->getClientId(),
                'acr_values' => $acrValues,
                'prompt' => $prompt,
            ], $pkceCode ? [
                'code_challenge' => trim(strtr(base64_encode(hash('sha256', $pkceCode, true)), '+/', '-_'), '='),
                'code_challenge_method' => $pkceMethod,
            ] : []));
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
     * @param array $scope
     * @param array $audience
     * @param int|null $shopId
     *
     * @return AccessToken access token
     *
     * @throws OAuth2Exception
     */
    public function getAccessTokenByAuthorizationCode(
        $code,
        $pkceCode = null,
        array $scope = [],
        array $audience = [],
        $shopId = null
    ) {
        $this->assertClientExists();

        $response = $this->getHttpClient()->post(
            $this->getWellKnown()->token_endpoint,
            [
                Request::FORM => array_merge([
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->oAuth2Client->getClientId(),
                    'client_secret' => $this->oAuth2Client->getClientSecret(),
                    'code' => $code,
                    'scope' => implode(' ', $scope),
                    'audience' => implode(' ', $audience),
                    'redirect_uri' => $this->getOAuth2Client()->getRedirectUri([], $shopId),
                ], $pkceCode ? [
                    'code_verifier' => $pkceCode,
                ] : []),
            ]
        );

        if (!$response->isSuccessful) {
            throw new OAuth2ServerException($response, 'Unable to get access token');
        }

        return new AccessToken($response->body);
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
                Request::FORM => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->oAuth2Client->getClientId(),
                    'refresh_token' => $refreshToken,
                ],
            ]
        );

        if (!$response->isSuccessful) {
            throw new OAuth2ServerException($response, 'Unable to refresh access token');
        }

        return new AccessToken($response->body);
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
                Request::HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ]),
            ]
        );

        if (!$response->isSuccessful) {
            throw new OAuth2ServerException($response, 'Unable to get user infos');
        }

        return new UserInfo($response->body);
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
     * @return OAuth2Client
     */
    public function getOAuth2Client()
    {
        return $this->oAuth2Client;
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
        if (!$this->oAuth2Client->exists()) {
            throw new OAuth2Exception('OAuth2 client not configured');
        }
    }
}
