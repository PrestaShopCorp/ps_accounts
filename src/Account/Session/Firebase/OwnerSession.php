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
     * Delay in seconds before asking accounts-api again for a user token it didn't return:
     * about a user token lifetime, so no more calls than with a valid token
     */
    const TOKEN_UNAVAILABLE_DELAY = 3600;

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
     * The user token always belongs to the current point of contact:
     * - without a point of contact, or while accounts-api has no token for it (unless forced),
     *   there is no user token to get: don't call accounts-api;
     * - a token obtained for a previous point of contact is never served.
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
        $pointOfContactUuid = (string) $this->getStatusManager()->getPointOfContactUuid();

        if (!$pointOfContactUuid || (!$forceRefresh && $this->isTokenUnavailable($pointOfContactUuid))) {
            if ($this->hasStoredToken()) {
                $this->cleanup();
            }

            return $this->getToken();
        }

        if (!$this->tokenBelongsTo($pointOfContactUuid)) {
            $this->cleanup();
        }

        return parent::getValidToken($forceRefresh, $throw, $scope, $audience);
    }

    /**
     * @return bool
     */
    public function isEmailVerified()
    {
        $pointOfContactUuid = (string) $this->getStatusManager()->getPointOfContactUuid();

        // the stored token may only be trusted once known to belong to the current point of contact
        if ($pointOfContactUuid && $this->tokenBelongsTo($pointOfContactUuid)) {
            return parent::isEmailVerified();
        }

        return (bool) $this->getValidToken(false, false)->getJwt()->claims()->get('email_verified');
    }

    /**
     * accounts-api returned no token for this point of contact:
     * don't ask again before the delay is over.
     *
     * @param string $pointOfContactUuid
     *
     * @return void
     */
    public function setTokenUnavailable($pointOfContactUuid)
    {
        $this->configurationRepository->updateUserTokenUnavailable((string) json_encode([
            'pointOfContactUuid' => (string) $pointOfContactUuid,
            'until' => time() + static::TOKEN_UNAVAILABLE_DELAY,
        ]));
    }

    /**
     * @return void
     */
    public function clearTokenUnavailable()
    {
        $this->configurationRepository->updateUserTokenUnavailable('');
    }

    /**
     * @param string $pointOfContactUuid
     *
     * @return bool
     */
    protected function isTokenUnavailable($pointOfContactUuid)
    {
        $marker = json_decode((string) $this->configurationRepository->getUserTokenUnavailable(), true);

        return is_array($marker)
            && isset($marker['pointOfContactUuid'], $marker['until'])
            && $marker['pointOfContactUuid'] === $pointOfContactUuid
            && time() < (int) $marker['until'];
    }

    /**
     * The owner is the point of contact the token was obtained for, recorded when it was stored:
     * comparing it needs no call and settles after one token call, even if accounts-api
     * returns a token whose subject differs from the point of contact.
     *
     * @param string $pointOfContactUuid
     *
     * @return bool true when there is no stored token to compare
     */
    protected function tokenBelongsTo($pointOfContactUuid)
    {
        if (!$this->hasStoredToken()) {
            return true;
        }

        $tokenOwnerUuid = (string) $this->configurationRepository->getUserTokenOwner();
        if ('' === $tokenOwnerUuid) {
            // token stored before its owner was recorded
            $tokenOwnerUuid = (string) $this->getToken()->getUuid();
        }

        return $tokenOwnerUuid === $pointOfContactUuid;
    }

    /**
     * @return bool
     */
    protected function hasStoredToken()
    {
        $token = $this->getToken();

        return '' !== (string) $token || '' !== (string) $token->getRefreshToken();
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
        $this->configurationRepository->updateUserTokenOwner('');
    }

    /**
     * A stored token belongs to the current point of contact, and ends any unavailability.
     *
     * @param string $token
     * @param string $refreshToken
     *
     * @return void
     */
    public function setToken($token, $refreshToken = null)
    {
        $this->configurationRepository->updateUserFirebaseIdAndRefreshToken($token, $refreshToken);

        $tokenOwnerUuid = '';
        if ($token) {
            $tokenOwnerUuid = (string) $this->getStatusManager()->getPointOfContactUuid();
            $this->clearTokenUnavailable();
        }
        $this->configurationRepository->updateUserTokenOwner($tokenOwnerUuid);
    }
}
