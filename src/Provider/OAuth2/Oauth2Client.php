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
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class Oauth2Client
{
    const QUERY_LOGOUT_CALLBACK_PARAM = 'oauth2Callback';

    /**
     * @var ConfigurationRepository
     */
    private $cfRepos;

    /**
     * @var Link
     */
    private $link;

    public function __construct(
        ConfigurationRepository $configurationRepository,
        Link $link
    ) {
        $this->cfRepos = $configurationRepository;
        $this->link = $link;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return (bool) $this->cfRepos->getOauth2ClientId() &&
            (bool) $this->cfRepos->getOauth2ClientSecret();
    }

    /**
     * @return void
     */
    public function delete()
    {
        $this->cfRepos->updateOauth2ClientId('');
        $this->cfRepos->updateOauth2ClientSecret('');
        $this->cfRepos->updateOauth2ClientRedirectUri('');
        $this->cfRepos->updateOauth2ClientPostLogoutRedirectUri('');
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param string $postLogoutRedirectUri
     *
     * @return void
     */
    public function update($clientId, $clientSecret, $redirectUri, $postLogoutRedirectUri)
    {
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->setRedirectUri($redirectUri);
        $this->setPostLogoutRedirectUri($postLogoutRedirectUri);
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->cfRepos->getOauth2ClientId();
    }

    /**
     * @param string $clientId
     *
     * @return void
     */
    public function setClientId($clientId)
    {
        $this->cfRepos->updateOauth2ClientId($clientId);
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->cfRepos->getOauth2ClientSecret();
    }

    /**
     * @param string $clientSecret
     *
     * @return void
     */
    public function setClientSecret($clientSecret)
    {
        $this->cfRepos->updateOauth2ClientSecret($clientSecret);
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->cfRepos->getOauth2ClientRedirectUri();
    }

    /**
     * @param string $redirectUri
     *
     * @return void
     */
    public function setRedirectUri($redirectUri)
    {
        $this->cfRepos->updateOauth2ClientRedirectUri($redirectUri);
    }

    /**
     * @return string
     */
    public function getPostLogoutRedirectUri()
    {
        return $this->cfRepos->getOauth2ClientPostLogoutRedirectUri();
    }

    /**
     * @param string $postLogoutRedirectUri
     *
     * @return void
     */
    public function setPostLogoutRedirectUri($postLogoutRedirectUri)
    {
        $this->cfRepos->updateOauth2ClientPostLogoutRedirectUri($postLogoutRedirectUri);
    }

    /**
     * @example http://my-shop.mydomain/admin-path/index.php?controller=AdminOAuth2PsAccounts
     * @example http://my-shop.mydomain/admin-path/modules/ps_accounts/oauth2
     *
     * @return string
     */
    public function generateRedirectUri()
    {
        if (defined('_PS_VERSION_')
            && version_compare(_PS_VERSION_, '9', '>=')) {
            //return $this->link->getAdminLink('SfAdminOAuth2PsAccounts', false);
            return $this->link->getAdminLink('AdminOAuth2PsAccounts', false, [
                'route' => 'ps_accounts_oauth2',
            ]);
        }

        return $this->link->getAdminLink('AdminOAuth2PsAccounts', false, [], [], true);
    }

    /**
     * @example http://my-shop.mydomain/admin-path/index.php?controller=AdminLogin&logout=1&oauth2Callback=1
     * @example http://my-shop.mydomain/admin-path/logout?oauth2Callback=1
     *
     * @return string
     */
    public function generatePostLogoutRedirectUri()
    {
        return $this->link->getAdminLink('AdminLogin', false, [], [
            'logout' => 1,
            self::QUERY_LOGOUT_CALLBACK_PARAM => 1,
        ], true);
    }

    /**
     * @return void
     */
    public function generateRedirectUris()
    {
        $this->setRedirectUri($this->generateRedirectUri());
        $this->setPostLogoutRedirectUri($this->generatePostLogoutRedirectUri());
    }
}
