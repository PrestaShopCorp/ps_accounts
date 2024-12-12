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
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\PrestaShop;

class ShopProvider extends PrestaShop
{
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
    public function getOauth2Url()
    {
        return $this->module->getParameter('ps_accounts.oauth2_url');
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
     * {@inheritDoc}
     */
    public function getAccessToken($grant, array $options = [])
    {
        $this->syncOauth2ClientProps();

        return parent::getAccessToken($grant, $options);
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
