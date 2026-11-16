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
     * Per shop (cloud shop id), accounts-api returned no token of the point of contact in this runtime:
     * it isn't asked again before the next one.
     *
     * @var array<string, bool>
     */
    protected static $noTokenReturned = [];

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
     * The stored token, served only to its point of contact: a token kept without point of contact,
     * or for a previous one, is not served.
     *
     * @return Token
     */
    public function getToken()
    {
        $token = $this->getStoredToken();

        if (!$this->tokenBelongsTo($token, (string) $this->getStatusManager()->getPointOfContactUuid())) {
            return new Token('');
        }

        return $token;
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
        if (!$this->resolvePointOfContactUuid() ||
            isset(self::$noTokenReturned[(string) $this->getStatusManager()->getCloudShopId()])
        ) {
            return new Token('');
        }

        return parent::getValidToken($forceRefresh, $throw, $scope, $audience);
    }

    /**
     * @param string $refreshToken
     * @param array $scope
     * @param array $audience
     *
     * @return Token
     *
     * @throws RefreshTokenException
     */
    public function refreshToken($refreshToken = null, array $scope = [], array $audience = [])
    {
        $token = parent::refreshToken($refreshToken, $scope, $audience);

        // no token of the point of contact was returned: don't ask again for this shop in this runtime
        // (unlike Session's refresh errors, this one is per shop: multistore iterates over shops)
        if ('' === (string) $token) {
            self::$noTokenReturned[(string) $this->getStatusManager()->getCloudShopId()] = true;
        }

        return $token;
    }

    /**
     * @return void
     */
    public static function resetNoTokenReturned()
    {
        self::$noTokenReturned = [];
    }

    /**
     * @return bool
     */
    public function isEmailVerified()
    {
        $hadStoredToken = '' !== (string) $this->getStoredToken();

        $pointOfContactUuid = $this->resolvePointOfContactUuid();
        if (!$pointOfContactUuid) {
            return false;
        }

        // the previous point of contact's token was just cleaned up: read the new one's claim.
        // Without stored token, no call as on main: e.g. after IdentifyContactHandler's cleanup,
        // the new point of contact's token is only got by the next getValidToken()
        if ($hadStoredToken && '' === (string) $this->getStoredToken()) {
            return (bool) $this->getValidToken(false, false)->getJwt()->claims()->get('email_verified');
        }

        return parent::isEmailVerified();
    }

    /**
     * Point of contact, after cleaning up a stored token that belongs to someone else.
     *
     * @return string empty when there is no point of contact
     */
    protected function resolvePointOfContactUuid()
    {
        // without point of contact the stored token is kept but not served:
        // the cached status may only be transiently empty (identity migration or restore)
        $pointOfContactUuid = (string) $this->getStatusManager()->getPointOfContactUuid();

        if ($pointOfContactUuid && !$this->tokenBelongsTo($this->getStoredToken(), $pointOfContactUuid)) {
            // either the stored token or the cached status is stale
            $pointOfContactUuid = $this->refreshPointOfContactUuid();
            if (!$this->tokenBelongsTo($this->getStoredToken(), $pointOfContactUuid)) {
                $this->cleanup();
            }
        }

        return $pointOfContactUuid;
    }

    /**
     * @return Token
     */
    protected function getStoredToken()
    {
        return new Token(
            $this->configurationRepository->getUserFirebaseIdToken(),
            $this->configurationRepository->getUserFirebaseRefreshToken(),
            $this->resolveTokenExpirationLeeway()
        );
    }

    /**
     * accounts-api issues the user token with the point of contact uuid as subject.
     *
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
