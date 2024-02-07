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

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;

class ShopProvider extends PrestaShop
{
    use Guzzle5AdapterTrait;

    const QUERY_LOGOUT_CALLBACK_PARAM = 'oauth2Callback';

    /**
     * @var \Ps_accounts
     */
    private $module;

    /**
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * @var WellKnown
     */
    private $wellKnown;

    /**
     * @param array $options
     * @param array $collaborators
     *
     * @throws \Exception
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');
        $this->module = $module;
        $this->oauth2Client = $module->getService(Oauth2Client::class);

        $this->fetchWellKnown();

        // Disable certificate verification from local configuration
        $options['verify'] = (bool) $this->module->getParameter(
            'ps_accounts.check_api_ssl_cert'
        );

        if (method_exists($this, 'buildHttpClient')) {
            $collaborators['httpClient'] = $this->buildHttpClient($options);
        }

        parent::__construct(array_merge([
            'clientId' => $this->oauth2Client->getClientId(),
            'clientSecret' => $this->oauth2Client->getClientSecret(),
            'redirectUri' => $this->getRedirectUri(),
            'postLogoutCallbackUri' => $this->getPostLogoutRedirectUri(),
            'pkceMethod' => AbstractProvider::PKCE_METHOD_S256,
        ], $options), $collaborators);
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
     * @return PrestaShop
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->getParameter(
            'ps_accounts.oauth2_url_authorize',
            $this->wellKnown->authorization_endpoint ?:
                parent::getBaseAuthorizationUrl()
        );
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getParameter(
            'ps_accounts.oauth2_url_access_token',
            $this->wellKnown->token_endpoint ?:
                parent::getBaseAccessTokenUrl($params)
        );
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getParameter(
            'ps_accounts.oauth2_url_resource_owner_details',
            $this->wellKnown->userinfo_endpoint ?:
                parent::getResourceOwnerDetailsUrl($token)
        );
    }

    /**
     * @example  http://my-shop.mydomain/admin-path/index.php?controller=AdminOAuth2PsAccounts
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getRedirectUri()
    {
        /** @var Link $link */
        $link = $this->module->getService(Link::class);

        return $link->getAdminLink('AdminOAuth2PsAccounts', false);
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getBaseSessionLogoutUrl()
    {
        return $this->getParameter(
            'ps_accounts.oauth2_url_session_logout',
            $this->wellKnown->end_session_endpoint ?:
                parent::getBaseSessionLogoutUrl()
        );
    }

    /**
     * @example http://my-shop.mydomain/admin-path/index.php?controller=AdminLogin&logout=1&oauth2Callback=1
     *
     * @return string
     *
     * @throws \PrestaShopException
     */
    public function getPostLogoutRedirectUri()
    {
        /** @var Link $link */
        $link = $this->module->getService(Link::class);

        return $link->getAdminLink('AdminLogin', false, [], [
            'logout' => 1,
            self::QUERY_LOGOUT_CALLBACK_PARAM => 1,
        ]);
    }

    /**
     * @return Oauth2Client
     */
    public function getOauth2Client()
    {
        return $this->oauth2Client;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getParameter($name, $default = '')
    {
        return $this->module->hasParameter($name)
            ? $this->module->getParameter($name)
            : $default;
    }

    /**
     * @return void
     */
    private function fetchWellKnown()
    {
        try {
            $this->wellKnown = WellKnown::fetch(
                $this->getParameter('ps_accounts.oauth2_url'),
                (bool)$this->module->getParameter('ps_accounts.check_api_ssl_cert')
            );
        } catch (\Exception $e) {
            $this->wellKnown = new WellKnown([]);
        }
    }
}
