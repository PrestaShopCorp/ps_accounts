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
use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Session\Session;
use PrestaShop\Module\PsAccounts\Account\Session\SessionInterface;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

abstract class FirebaseSession extends Session implements SessionInterface
{
    /**
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Per shop (cloud shop id), time before which a status that couldn't be fetched isn't asked for again.
     * Shared by the owner and shop sessions, which both refresh the point of contact.
     * Kept in memory: it only lasts as long as the PHP process (a request, or a CLI worker), at most a cache lifetime.
     *
     * @var array<string, int>
     */
    protected static $statusUnavailableUntil = [];

    public function __construct(ShopSession $shopSession)
    {
        parent::__construct();

        $this->shopSession = $shopSession;
    }

    /**
     * @return void
     */
    public static function resetStatusUnavailable()
    {
        self::$statusUnavailableUntil = [];
    }

    /**
     * @return AccountsService
     */
    public function getAccountsService()
    {
        return $this->module->getService(AccountsService::class);
    }

    /**
     * @return Firebase\OwnerSession
     */
    public function getOwnerSession()
    {
        return $this->module->getService(Firebase\OwnerSession::class);
    }

    /**
     * @return Firebase\ShopSession
     */
    public function getShopSession()
    {
        return $this->module->getService(Firebase\ShopSession::class);
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
        try {
            $token = $this->shopSession->getValidToken();
            $cloudShopId = $this->getStatusManager()->getCloudShopId();

            $this->refreshFirebaseTokens($cloudShopId, $token);
        } catch (RefreshTokenException $e) {
            Logger::getInstance()->error('Unable to get or refresh owner/shop token : ' . $e->getMessage());
            throw $e;
        }

        return $this->getToken();
    }

    /**
     * @param string $cloudShopId
     * @param Token $token
     *
     * @return void
     *
     * @throws RefreshTokenException
     */
    protected function refreshFirebaseTokens($cloudShopId, $token)
    {
        try {
            $firebaseTokens = $this->getAccountsService()->firebaseTokens($cloudShopId, $token);
        } catch (AccountsException $e) {
            throw new RefreshTokenException($e->getMessage());
        }

        $shopToken = new Token(
            $firebaseTokens->shop->token,
            $firebaseTokens->shop->refreshToken
        );

        $pointOfContactToken = null;
        if (isset($firebaseTokens->pointOfContact->token) && isset($firebaseTokens->pointOfContact->refreshToken)) {
            $pointOfContactToken = new Token(
                $firebaseTokens->pointOfContact->token,
                $firebaseTokens->pointOfContact->refreshToken
            );
        }

        // saving both tokens here
        $this->getShopSession()->setToken((string) $shopToken->getJwt(), $shopToken->getRefreshToken());

        if (isset($pointOfContactToken)) {
            $pointOfContactUuid = (string) $this->getStatusManager()->getPointOfContactUuid();
            if (!$this->tokenBelongsTo($pointOfContactToken, $pointOfContactUuid)) {
                // either the token or the cached status is stale: tell from the status read again
                $pointOfContactUuid = $this->refreshPointOfContactUuid();
            }

            // when the status couldn't be read, whose token it is can't be told: the stored one is kept
            if (null !== $pointOfContactUuid) {
                if ($this->tokenBelongsTo($pointOfContactToken, $pointOfContactUuid)) {
                    $this->getOwnerSession()->setToken((string) $pointOfContactToken->getJwt(), $pointOfContactToken->getRefreshToken());
                } else {
                    // accounts-api still returns the previous point of contact's token: keep none
                    $this->getOwnerSession()->cleanup();
                }
            }
        } else {
            // accounts-api has no point of contact for this shop (it always returns the field, empty then):
            // never keep serving a previous one's token
            $this->getOwnerSession()->cleanup();

            // a cached point of contact is then likely stale: read the status again once its cache has expired,
            // so that no user token is asked for again
            if ($this->getStatusManager()->getPointOfContactUuid()) {
                $this->refreshPointOfContactUuid(false);
            }
        }
    }

    /**
     * accounts-api issues the user token with the point of contact uuid as subject.
     *
     * @param Token $token
     * @param string $pointOfContactUuid
     *
     * @return bool true for an empty token (nothing to compare), false for a token without subject
     */
    protected function tokenBelongsTo(Token $token, $pointOfContactUuid)
    {
        if ('' === (string) $token) {
            return true;
        }

        $tokenOwnerUuid = (string) $token->getUuid();

        return '' !== $tokenOwnerUuid && $tokenOwnerUuid === (string) $pointOfContactUuid;
    }

    /**
     * Point of contact from the status read again from accounts-api: whatever its cache when the cached one
     * disagrees with a token, once its cache has expired otherwise.
     * After a failure, the status isn't asked for again for this shop before a cache lifetime.
     *
     * @param bool $force
     *
     * @return string|null empty when there is no point of contact, null when the status couldn't be read
     */
    protected function refreshPointOfContactUuid($force = true)
    {
        $statusManager = $this->getStatusManager();
        $cloudShopId = (string) $statusManager->getCloudShopId();

        if (isset(self::$statusUnavailableUntil[$cloudShopId]) && time() < self::$statusUnavailableUntil[$cloudShopId]) {
            return null;
        }

        $throwException = $statusManager->getThrowException(false);
        try {
            $pointOfContactUuid = (string) $statusManager->withThrowException(true)
                ->getStatus(false, $force ? 0 : StatusManager::CACHE_TTL)
                ->pointOfContactUuid;
            unset(self::$statusUnavailableUntil[$cloudShopId]);
        } catch (\Exception $e) {
            Logger::getInstance()->error('Unable to refresh the shop status: ' . $e->getMessage());
            self::$statusUnavailableUntil[$cloudShopId] = time() + StatusManager::CACHE_TTL;
            $pointOfContactUuid = null;
        } finally {
            // the caller's setting is restored, whatever is raised
            $statusManager->withThrowException($throwException);
        }

        return $pointOfContactUuid;
    }
}
