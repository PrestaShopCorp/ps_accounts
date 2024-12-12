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

use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessToken;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PrestaShopSession
{
    const TOKEN_NAME = 'accessToken';

    /**
     * @var SessionInterface|mixed
     */
    private $session;

    /**
     * @var ShopProvider
     */
    private $provider;

    /**
     * @param mixed $session
     * @param ShopProvider $provider
     */
    public function __construct($session, ShopProvider $provider)
    {
        $this->session = $session;
        $this->provider = $provider;
    }

    /**
     * @return string|null
     *
     * @throws IdentityProviderException
     */
    public function getOrRefreshAccessToken()
    {
        $token = $this->getTokenProvider();
        if (($token instanceof AccessToken) && $token->hasExpired()) {
            /** @var AccessToken $token */
            $token = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $token->getRefreshToken(),
            ]);
            $this->setTokenProvider($token);
        }

        return $this->getAccessToken();
    }

    /**
     * @return string|null
     */
    public function getIdToken()
    {
        $token = $this->getTokenProvider();

        return ($token instanceof AccessToken) ? $token->getValues()['id_token'] : null;
    }

    /**
     * @return string|null
     */
    public function getAccessToken()
    {
        $token = $this->getTokenProvider();

        return ($token instanceof AccessToken) ? $token->getToken() : null;
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
     * @return PrestaShopUser
     */
    public function getPrestashopUser()
    {
        return $this->provider->getResourceOwner($this->getTokenProvider());
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
        if (!$this->provider->getOauth2Client()->exists()) {
            $this->clear();
        }

        return $this->session->get(self::TOKEN_NAME);
    }
}
