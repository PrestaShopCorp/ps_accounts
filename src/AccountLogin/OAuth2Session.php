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

namespace PrestaShop\Module\PsAccounts\AccountLogin;

use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\UserInfo;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OAuth2Session
{
    const TOKEN_NAME = 'accessToken';

    /**
     * @var SessionInterface|mixed
     */
    private $session;

    /**
     * @var OAuth2Service
     */
    private $oAuth2Service;

    /**
     * @var OAuth2Client
     */
    private $oauth2Client;

    /**
     * @param mixed $session
     * @param OAuth2Service $oAuth2Service
     * @param OAuth2Client $oauth2Client
     */
    public function __construct($session, OAuth2Service $oAuth2Service, OAuth2Client $oauth2Client)
    {
        $this->session = $session;
        $this->oAuth2Service = $oAuth2Service;
        $this->oauth2Client = $oauth2Client;
    }

    /**
     * @return string|null
     */
    public function getOrRefreshAccessToken()
    {
        $token = $this->getTokenProvider();
        if ($token instanceof AccessToken && (new Token($token->access_token))->isExpired()) {
            try {
                $token = $this->oAuth2Service->refreshAccessToken($token->refresh_token);
                $this->setTokenProvider($token);
            } catch (OAuth2Exception $e) {
            }
        }

        return $this->getAccessToken();
    }

    /**
     * @return string|null
     */
    public function getIdToken()
    {
        $token = $this->getTokenProvider();

        return ($token instanceof AccessToken) ? $token->id_token : null;
    }

    /**
     * @return string|null
     */
    public function getAccessToken()
    {
        $token = $this->getTokenProvider();

        return ($token instanceof AccessToken) ? $token->access_token : null;
    }

    /**
     * @param AccessToken $token
     *
     * @return void
     */
    public function setTokenProvider(AccessToken $token)
    {
        $this->session->set(self::TOKEN_NAME, $token);
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->session->has(self::TOKEN_NAME);
    }

    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        return $this->oAuth2Service->getUserInfo($this->getAccessToken());
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->session->remove(self::TOKEN_NAME);
    }

    /**
     * @return AccessToken|null
     */
    private function getTokenProvider()
    {
        if (!$this->oauth2Client->exists()) {
            $this->clear();
        }

        return $this->session->get(self::TOKEN_NAME);
    }
}
