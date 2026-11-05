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

namespace PrestaShop\Module\PsAccounts\Account\Session\Firebase;

use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Session\SessionInterface;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class OwnerSession extends FirebaseSession implements SessionInterface
{
    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var int
     */
    protected $tokenExpirationLeeway;

    /**
     * @param ConfigurationRepository $configurationRepository
     * @param \PrestaShop\Module\PsAccounts\Account\Session\ShopSession $shopSession
     * @param int $tokenExpirationLeeway tolerance in seconds when checking
     *                                   the local token expiration before deciding to refresh it
     */
    public function __construct(
        ConfigurationRepository $configurationRepository,
        \PrestaShop\Module\PsAccounts\Account\Session\ShopSession $shopSession,
        $tokenExpirationLeeway = 0
    ) {
        $this->configurationRepository = $configurationRepository;
        $this->tokenExpirationLeeway = (int) $tokenExpirationLeeway;

        parent::__construct($shopSession);
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return new Token(
            $this->configurationRepository->getUserFirebaseIdToken(),
            $this->configurationRepository->getUserFirebaseRefreshToken(),
            $this->resolveTokenExpirationLeeway()
        );
    }

    /**
     * The user token always belongs to the current point of contact
     * (accounts-api issues it with the point of contact uuid as subject):
     * - without a point of contact, there is no user token to get: don't call accounts-api;
     * - a token of another point of contact is never served.
     *
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
        $pointOfContactUuid = $this->resolvePointOfContactUuid();
        if (!$pointOfContactUuid) {
            return new Token('');
        }

        $token = parent::getValidToken($forceRefresh, $throw, $scope, $audience);

        // accounts-api may still be returning the token of the previous point of contact
        return $this->tokenBelongsTo($token, $pointOfContactUuid) ? $token : new Token('');
    }

    /**
     * @return bool
     */
    public function isEmailVerified()
    {
        if (!$this->resolvePointOfContactUuid()) {
            return false;
        }

        return parent::isEmailVerified();
    }

    /**
     * Point of contact the stored token, if any, belongs to. When the token belongs to someone else,
     * either the token or the cached status is stale: the status is fetched again to tell,
     * and the token of a previous point of contact is cleaned up.
     *
     * @return string empty when there is no point of contact, or when it can't be told
     */
    protected function resolvePointOfContactUuid()
    {
        $pointOfContactUuid = (string) $this->getStatusManager()->getPointOfContactUuid();

        // without point of contact the stored token is kept but not served:
        // the cached status may only be transiently empty (identity migration or restore)
        if (!$pointOfContactUuid || $this->tokenBelongsTo($this->getToken(), $pointOfContactUuid)) {
            return $pointOfContactUuid;
        }

        $pointOfContactUuid = $this->fetchPointOfContactUuid();
        if (null === $pointOfContactUuid) {
            return '';
        }

        if (!$this->tokenBelongsTo($this->getToken(), $pointOfContactUuid)) {
            $this->cleanup();
        }

        return $pointOfContactUuid;
    }

    /**
     * @param Token $token
     * @param string $pointOfContactUuid
     *
     * @return bool true for an empty token
     */
    protected function tokenBelongsTo(Token $token, $pointOfContactUuid)
    {
        $tokenOwnerUuid = (string) $token->getUuid();

        return '' === $tokenOwnerUuid || $tokenOwnerUuid === $pointOfContactUuid;
    }

    /**
     * @return int
     */
    protected function resolveTokenExpirationLeeway()
    {
        $leeway = $this->configurationRepository->getTokenExpirationLeeway();

        return is_int($leeway) ? $leeway : $this->tokenExpirationLeeway;
    }

    /**
     * @return void
     */
    public function cleanup()
    {
        $this->configurationRepository->updateUserFirebaseIdAndRefreshToken('', '');
    }

    /**
     * @param string $token
     * @param string $refreshToken
     *
     * @return void
     */
    public function setToken($token, $refreshToken = null)
    {
        $this->configurationRepository->updateUserFirebaseIdAndRefreshToken($token, $refreshToken);
    }
}
