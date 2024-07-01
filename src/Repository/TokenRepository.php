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

namespace PrestaShop\Module\PsAccounts\Repository;

use Exception;
use PrestaShop\Module\PsAccounts\Account\Session\Session;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token;

/**
 * @deprecated
 */
abstract class TokenRepository
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session $session
     */
    public function __construct(
        Session $session
    ) {
        $this->session = $session;
    }

    /**
     * @return Token|null
     */
    public function getToken()
    {
        $token = $this->session->getToken()->getJwt();

        return $token instanceof NullToken ? null : $token;
    }

    /**
     * @return string
     */
    public function getTokenUuid()
    {
        return $this->session->getToken()->getUuid();
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->session->getToken()->getRefreshToken();
    }

    /**
     * @return void
     */
    public function cleanupCredentials()
    {
        $this->session->cleanup();
    }

    /**
     * @param string $idToken
     * @param string $refreshToken
     *
     * @return void
     */
    public function updateCredentials($idToken, $refreshToken)
    {
        $this->session->setToken($idToken, $refreshToken);
    }

    /**
     * @param bool $forceRefresh
     *
     * @return Token|null
     */
    public function getOrRefreshToken($forceRefresh = false)
    {
        try {
            return $this->session->getValidToken($forceRefresh)->getJwt();
        } catch (RefreshTokenException $e) {
            return null;
        }
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function isTokenExpired()
    {
        return $this->session->getToken()->isExpired();
    }
}
