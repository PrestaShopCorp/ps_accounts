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

namespace PrestaShop\Module\PsAccounts\Account\Session;

use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Log\Logger;

abstract class Session implements SessionInterface
{
    /**
     * @var array
     */
    protected $refreshTokenErrors = [];

    /**
     * @param bool $forceRefresh
     *
     * @return Token
     */
    public function getOrRefreshToken($forceRefresh = false)
    {
        /*
         * Avoid multiple refreshToken calls in the same runtime:
         * if it fails once, it will subsequently fail
         */
        if ($this->getRefreshTokenErrors(static::class)) {
            $this->setToken('');

            return $this->getToken();
        }

        $currentToken = $this->getToken();
        if (true === $forceRefresh || $currentToken->isExpired()) {
            try {
                $this->refreshToken(null);
            } catch (RefreshTokenException $e) {
                $this->setToken('');
                $this->setRefreshTokenErrors(static::class);
                Logger::getInstance()->error($e->getMessage());
            }
        }

        return $this->getToken();
    }

    /**
     * @return bool
     */
    public function isEmailVerified()
    {
        $jwt = $this->getToken()->getJwt();

        // FIXME : just query sso api and don't refresh token everytime
        if (!$jwt instanceof NullToken &&
            !$jwt->claims()->get('email_verified')
        ) {
            $jwt = $this->getOrRefreshToken(true)->getJwt();
        }

        return (bool) $jwt->claims()->get('email_verified');
    }

    /**
     * @param string $refreshToken
     *
     * @return bool
     */
    public function getRefreshTokenErrors($refreshToken)
    {
        return isset($this->refreshTokenErrors[$refreshToken]) && $this->refreshTokenErrors[$refreshToken];
    }

    /**
     * @return void
     */
    public function resetRefreshTokenErrors()
    {
        $this->refreshTokenErrors = [];
    }

    /**
     * @param string $refreshToken
     *
     * @return void
     */
    protected function setRefreshTokenErrors($refreshToken)
    {
        $this->refreshTokenErrors[$refreshToken] = true;
    }
}
