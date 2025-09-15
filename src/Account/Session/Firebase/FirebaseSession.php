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

    public function __construct(ShopSession $shopSession)
    {
        parent::__construct();

        $this->shopSession = $shopSession;
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
            $this->getOwnerSession()->setToken((string) $pointOfContactToken->getJwt(), $pointOfContactToken->getRefreshToken());
        }
    }
}
