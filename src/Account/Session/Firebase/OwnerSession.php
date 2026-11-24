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
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class OwnerSession extends FirebaseSession implements SessionInterface
{
    /**
     * Per shop (cloud shop id), time before which accounts-api isn't asked again for a user token
     * after it returned no valid one of the point of contact.
     * Kept in memory: it only lasts as long as the PHP process (a request, or a CLI worker), at most a cache lifetime.
     *
     * @var array<string, int>
     */
    protected static $noTokenReturnedUntil = [];

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
     * In both cases an empty token is returned without exception, whatever $throw:
     * as on main, where accounts-api returned no token of the point of contact.
     * A token still expired after a refresh (e.g. the previous point of contact's one,
     * kept while the status can't be read) isn't served either.
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
        if (!$this->resolvePointOfContactUuid()) {
            return new Token('');
        }

        $token = parent::getValidToken($forceRefresh, $throw, $scope, $audience);

        return '' !== (string) $token && $token->isExpired() ? new Token('') : $token;
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
        // accounts-api returned no valid token of the point of contact lately: don't ask again before a cache lifetime
        // (unlike Session's refresh errors, this one is per shop: multistore iterates over shops)
        $cloudShopId = (string) $this->getStatusManager()->getCloudShopId();
        if (isset(self::$noTokenReturnedUntil[$cloudShopId]) && time() < self::$noTokenReturnedUntil[$cloudShopId]) {
            return $this->getToken();
        }

        $token = parent::refreshToken($refreshToken, $scope, $audience);

        if ('' === (string) $token || $token->isExpired()) {
            self::$noTokenReturnedUntil[$cloudShopId] = time() + StatusManager::CACHE_TTL;
        } else {
            unset(self::$noTokenReturnedUntil[$cloudShopId]);
        }

        return $token;
    }

    /**
     * @return void
     */
    public static function resetNoTokenReturned()
    {
        self::$noTokenReturnedUntil = [];
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

        // the previous point of contact's token was just cleaned up: get the new one's, then read it as usual.
        // Without stored token, no call as on main: e.g. after IdentifyContactHandler's cleanup,
        // the new point of contact's token is only got by the next getValidToken()
        if ($hadStoredToken && '' === (string) $this->getStoredToken()) {
            $this->getValidToken(false, false);
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
        $pointOfContactUuid = (string) $this->getStatusManager()->getPointOfContactUuid();

        // a stored token of another point of contact, or while the cached status names none:
        // either the stored token or the cached status is stale, tell from the status read again
        // (at most once per stored token: it is then either confirmed or cleaned up)
        if (!$this->tokenBelongsTo($this->getStoredToken(), $pointOfContactUuid)) {
            $pointOfContactUuid = $this->refreshPointOfContactUuid();

            if (null === $pointOfContactUuid) {
                // the status couldn't be read: the stored token is kept, but not served
                $pointOfContactUuid = '';
            } elseif (!$this->tokenBelongsTo($this->getStoredToken(), $pointOfContactUuid)) {
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
