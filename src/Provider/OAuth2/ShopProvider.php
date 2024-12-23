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

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Provider\AbstractProvider;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\CachedFile;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\PrestaShop;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\ServiceContainer;

class ShopProvider extends PrestaShop
{
    /**
     * @var Oauth2Client
     */
    protected $oauth2Client;

    /**
     * @var string
     */
    protected $oauth2Url;

    /**
     * @param array $options
     * @param array $collaborators
     *
     * @throws \Exception
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        if (method_exists($this, 'buildHttpClient')) {
            $collaborators['httpClient'] = $this->buildHttpClient($options);
        }
        //parent::__construct(array_merge([], $options), $collaborators);
        parent::__construct($options, $collaborators);
    }

    /**
     * @param ServiceContainer $container
     *
     * @return static
     */
    public function create(ServiceContainer $container)
    {
        $cacheDir = _PS_CACHE_DIR_ . DIRECTORY_SEPARATOR . '/ps_accounts';
        $cacheTtl = (int) $container->getParameterWithDefault(
            'ps_accounts.openid_configuration_cache_ttl',
            (string) (60 * 60 * 24)
        );

        $link = $container->get(Link::class);

        // http://my-shop.mydomain/admin-path/index.php?controller=AdminOAuth2PsAccounts
        $redirectUri = $link->getAdminLink('AdminOAuth2PsAccounts', false);

        // http://my-shop.mydomain/admin-path/index.php?controller=AdminLogin&logout=1&oauth2Callback=1
        $postLogoutRedirectUri = $link->getAdminLink('AdminLogin', false, [], [
            'logout' => 1,
            PrestaShopLogoutTrait::getQueryLogoutCallbackParam() => 1,
        ]);

        /** @var Oauth2Client $oauth2Client */
        $oauth2Client = $container->get(Oauth2Client::class);

        return new ShopProvider(
            [
                'redirectUri' => $redirectUri,
                'postLogoutCallbackUri' => $postLogoutRedirectUri,
                'oauth2Url' => $container->getParameter('ps_accounts.oauth2_url'),
                'oauth2Client' => $container->get(Oauth2Client::class),
                'clientId' => $oauth2Client->getClientId(),
                'clientSecret' => $oauth2Client->getClientSecret(),
                'cachedJwks' => new CachedFile($cacheDir . '/jwks.json'),
                'cachedWellKnown' => new CachedFile(
                    $cacheDir . '/openid-configuration.json',
                    $cacheTtl
                ),
                'pkceMethod' => AbstractProvider::PKCE_METHOD_S256,
                // Disable certificate verification from local configuration
                'verify' => (bool) $container->getParameter(
                    'ps_accounts.check_api_ssl_cert'
                ),
            ]
        );
    }

    /**
     * @return string
     */
    public function getOauth2Url()
    {
        return $this->oauth2Url;
    }

    /**
     * @return Oauth2Client
     */
    public function getOauth2Client()
    {
        return $this->oauth2Client;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessToken($grant, array $options = [])
    {
        $this->syncOauth2ClientProps();

        return parent::getAccessToken($grant, $options);
    }

    /**
     * @param array $options
     *
     * @return array|string[]
     */
    protected function getAllowedClientOptions(array $options)
    {
        return array_merge(parent::getAllowedClientOptions($options), [
            'verify',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getAuthorizationParameters(array $options)
    {
        $this->syncOauth2ClientProps();

        return parent::getAuthorizationParameters($options);
    }

    /**
     * @return void
     */
    private function syncOauth2ClientProps()
    {
        $this->clientId = $this->getOauth2Client()->getClientId();
        $this->clientSecret = $this->getOauth2Client()->getClientSecret();
    }
}
