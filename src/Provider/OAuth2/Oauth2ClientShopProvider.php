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

use League\OAuth2\Client\Token\AccessToken;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;

class Oauth2ClientShopProvider extends PrestaShop
{
    public const SESSION_ACCESS_TOKEN_NAME = 'accessToken';
    public const QUERY_LOGOUT_CALLBACK_PARAM = 'oauth2Callback';

    /**
     * @var \Ps_accounts
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

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
        $this->context = $module->getContext();
        $this->configuration = $module->getService(ConfigurationRepository::class);

        // Disable certificate verification from local configuration
        $options['verify'] = (bool) $this->module->getParameter(
            'ps_accounts.check_api_ssl_cert'
        );

        parent::__construct(array_merge([
            'clientId' => $this->configuration->getOauth2ClientId(),
            'clientSecret' => $this->configuration->getOauth2ClientSecret(),
            'redirectUri' => $this->getRedirectUri(),
            'postLogoutCallbackUri' => $this->getPostLogoutRedirectUri(),
        ], $options), $collaborators);
    }

    protected function getAllowedClientOptions(array $options)
    {
        return array_merge(parent::getAllowedClientOptions($options), [
            'verify',
        ]);
    }

    public static function create(): PrestaShop
    {
        return new self();
    }

    /**
     * @throws \Exception
     */
    public function getParameter(string $name, string $default): string
    {
        return $this->module->hasParameter($name)
            ? $this->module->getParameter($name)
            : $default;
    }

    /**
     * @throws \Exception
     */
    public function getBaseAuthorizationUrl(): string
    {
        return $this->getParameter(
            'ps_accounts.oauth2_url_authorize',
            parent::getBaseAuthorizationUrl()
        );
    }

    /**
     * @throws \Exception
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->getParameter(
            'ps_accounts.oauth2_url_access_token',
            parent::getBaseAccessTokenUrl($params)
        );
    }

    /**
     * @throws \Exception
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->getParameter(
            'ps_accounts.oauth2_url_resource_owner_details',
            parent::getResourceOwnerDetailsUrl($token)
        );
    }

    /**
     * @example  http://my-shop.mydomain/admin-path/index.php?controller=AdminOAuth2PsAccounts
     *
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->context->link->getAdminLink('AdminOAuth2PsAccounts', false);
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getBaseSessionLogoutUrl(): string
    {
        return $this->getParameter(
            'ps_accounts.oauth2_url_session_logout',
            parent::getBaseSessionLogoutUrl()
        );
    }

    /**
     * @example http://my-shop.mydomain/admin-path/index.php?controller=AdminLogin&logout=1&oauth2Callback=1
     *
     * @return string
     */
    public function getPostLogoutRedirectUri(): string
    {
        return $this->context->link->getAdminLink('AdminLogin', false, [], [
            'logout' => 1,
            self::QUERY_LOGOUT_CALLBACK_PARAM => 1,
        ]);
    }
}
