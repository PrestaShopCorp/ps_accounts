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
               $cacheDir = null,
               $defaultTimeout = 20,
               $sslCheck = true
    ) {
        $this->baseUri = $baseUri;
        $this->client = $client;
        $this->link = $link;
        $this->defaultTimeout = $defaultTimeout;
        $this->sslCheck = $sslCheck;

        // FIXME configuration parameter
        $this->cachedWellKnown = new CachedFile(
            $cacheDir . '/openid-configuration.json',
            60 * 60 * 24
        );
    }

    /**
     * @return HttpClient
     */
    private function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = (new Factory())->create([
                'name' => static::class,
                'baseUri' => $this->baseUri,
                'headers' => $this->getHeaders(),
                'timeout' => $this->defaultTimeout,
                'sslCheck' => $this->sslCheck,
                'objectResponse' => true,
            ]);
        }

        return $this->httpClient;
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
     */
    public function getWellKnown()
    {
        /* @phpstan-ignore-next-line */
        if (!isset($this->wellKnown) || $this->cachedWellKnown->isExpired()) {
            try {
                $this->wellKnown = new WellKnown(json_decode($this->getCachedWellKnown(), true));
            } catch (\Throwable $e) {
                /* @phpstan-ignore-next-line */
            } catch (\Exception $e) {
            }
            if (isset($e)) {
                $this->wellKnown = new WellKnown();
            }
        }

        return $this->wellKnown;
    }

    /**
     * @param bool $forceRefresh
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getCachedWellKnown($forceRefresh = false)
    {
        if (null === $this->cachedWellKnown) {
            throw new \Exception('Cache file not configured');
        }

        if ($this->cachedWellKnown->isExpired() || $forceRefresh) {
            $this->cachedWellKnown->write(
                json_encode($this->fetchWellKnown($this->baseUri), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }

        return (string) $this->cachedWellKnown->read();
    }

    /**
     * @param string $url
     *
     * @return array
     */
    protected function fetchWellKnown($url = null)
    {
        $wellKnownUrl = $url ?: $this->baseUri;
        if (\strpos($wellKnownUrl, '/.well-known') === \false) {
            $wellKnownUrl = \preg_replace('/\\/?$/', '/.well-known/openid-configuration', $wellKnownUrl);
        }

        /** @var Response $response */
        $response = $this->getHttpClient()->get($wellKnownUrl);

        return $response->body;
    }

    /**
     * @param array $scope
     * @param array $audience
     *
     * @return AccessToken access token
     */
    public function getAccessTokenByClientCredentials(array $scope = [], array $audience = [])
    {
        /** @var Response $response */
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

        if (!$response->status) {
            throw new OAuth2Exception('Unable to get access token');
        }

        return new AccessToken($response->body);
    }

    /**
     * @param string $state
     * @param string $redirectUri
     * @param string|null $pkceCode
     * @param string $pkceMethod
     * @param string $uiLocales
     *
     * @return string authorization flow uri
     */
    public function getAuthorizationUri(
        $state,
        $redirectUri,
        $pkceCode = null,
        $pkceMethod = 'S256',
        $uiLocales = 'fr'
    ) {
        return $this->getWellKnown()->authorization_endpoint . '?' .
            http_build_query(array_merge([
                'ui_locales' => $uiLocales,
                'state' => $state,
                'scope' => 'openid offline_access',
                'response_type' => 'code',
                'approval_prompt' => 'auto',
                'redirect_uri' => $redirectUri,
                'client_id' => $this->client->getClientId(),
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
     */
    public function getAccessTokenByAuthorizationCode(
        $code,
        $pkceCode = null,
        $redirectUri = null,
        array $scope = [],
        array $audience = []
    ) {
        /** @var Response $response */
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

        if (!$response->status) {
            throw new OAuth2Exception('Unable to get access token');
        }

        return new AccessToken($response->body);
    }

    /**
     * @param string $refreshToken
     *
     * @return AccessToken
     */
    public function refreshAccessToken($refreshToken)
    {
        /** @var Response $response */
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

        if (!$response->status) {
            throw new OAuth2Exception('Unable to refresh access token');
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
        /** @var Response $response */
        $response = $this->getHttpClient()->get(
            $this->getWellKnown()->userinfo_endpoint,
            [
                Options::REQ_HEADERS => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ]),
            ]
        );

        if (!$response->status) {
            throw new OAuth2Exception('Unable to get user infos');
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
}
