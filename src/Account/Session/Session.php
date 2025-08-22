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

use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Log\Logger;

abstract class Session implements SessionInterface
{
    /**
     * @var array
     */
    protected $refreshTokenErrors = [];

    /**
     * @var \Ps_accounts
     */
    protected $module;

    public function __construct()
    {
        /* @phpstan-ignore-next-line */
        $this->module = \Module::getInstanceByName('ps_accounts');
    }

    /**
     * @deprecated use getValidToken instead
     *
     * @param bool $forceRefresh
     *
     * @return Token
     */
    public function getOrRefreshToken($forceRefresh = false)
    {
        return $this->getValidToken($forceRefresh, false);
    }

    /**
     * @param bool $forceRefresh
     * @param bool $throw
     * @param array $scope
     * @param array $audience
     *
     * @return Token
     *
     * @throws RefreshTokenException
     */
    public function getValidToken($forceRefresh = false, $throw = true, array $scope = [], array $audience = [])
    {
        /*
         * Avoid multiple refreshToken calls in the same runtime:
         * if it fails once, it will subsequently fail
         */
        if ($message = $this->getRefreshTokenErrors(static::class)) {
            $this->setToken('');

            if ($throw) {
                throw new RefreshTokenException('Unable to refresh shop token : ' . $message);
            }

            return $this->getToken();
        }

        if (true === $forceRefresh || false === $this->getToken()->isValid($scope, $audience)) {
            try {
                $this->refreshToken(null, $scope, $audience);
            } catch (RefreshTokenException $e) {
                $this->setToken('');
                $this->setRefreshTokenErrors(static::class, $e->getMessage());

                if ($throw) {
                    throw $e;
                }
                Logger::getInstance()->error($e->getMessage());
            }
        }

        return $this->getToken();
    }

    /**
     * @return bool
     *
     * @deprecated since v8.0.0
     */
    public function isEmailVerified()
    {
        try {
            $jwt = $this->getToken()->getJwt();

            // FIXME : just query sso api and don't refresh token everytime
            if (!$jwt instanceof NullToken &&
                !$jwt->claims()->get('email_verified')
            ) {
                $jwt = $this->getValidToken(true)->getJwt();
            }

            return (bool) $jwt->claims()->get('email_verified');
        } catch (RefreshTokenException $e) {
            return false;
        }
    }

    /**
     * @param string $refreshToken
     *
     * @return string|false
     */
    public function getRefreshTokenErrors($refreshToken)
    {
        return isset($this->refreshTokenErrors[$refreshToken]) ? $this->refreshTokenErrors[$refreshToken] : false;
    }

    /**
     * @return void
     */
    public function resetRefreshTokenErrors()
    {
        $this->refreshTokenErrors = [];
    }

    /**
     * @param string $className
     * @param string $message
     *
     * @return void
     */
    protected function setRefreshTokenErrors($className, $message)
    {
        $this->refreshTokenErrors[$className] = $message;
    }

    /**
     * @return StatusManager
     */
    protected function getStatusManager()
    {
        return $this->module->getService(StatusManager::class);
    }
}
