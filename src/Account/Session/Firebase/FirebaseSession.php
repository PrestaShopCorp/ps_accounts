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

use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Session\Session;
use PrestaShop\Module\PsAccounts\Account\Session\SessionInterface;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Log\Logger;

abstract class FirebaseSession extends Session implements SessionInterface
{
    /**
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * @var \Ps_accounts
     */
    private $module;

    public function __construct(ShopSession $shopSession)
    {
        $this->shopSession = $shopSession;

        /* @phpstan-ignore-next-line */
        $this->module = \Module::getInstanceByName('ps_accounts');
    }

    /**
     * @return AccountsClient
     */
    public function getAccountsClient()
    {
        return $this->module->getService(AccountsClient::class);
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
     *
     * @return Token
     *
     * @throws RefreshTokenException
     */
    public function refreshToken($refreshToken = null)
    {
        $token = $this->shopSession->getValidToken();

        try {
            $this->refreshFirebaseTokens($token);
        } catch (RefreshTokenException $e) {
            Logger::getInstance()->error('Unable to get or refresh owner/shop token : ' . $e->getMessage());
            throw $e;
        }

        return $this->getToken();
    }

    /**
     * @param Token $token
     *
     * @return void
     *
     * @throws RefreshTokenException
     */
    protected function refreshFirebaseTokens($token)
    {
        $response = $this->getAccountsClient()->firebaseTokens($token);

        $shopToken = $this->getFirebaseTokenFromResponse($response, 'shopToken', 'shopRefreshToken');
        $ownerToken = $this->getFirebaseTokenFromResponse($response, 'userToken', 'userRefreshToken');

        // saving both tokens here
        $this->getShopSession()->setToken((string) $shopToken->getJwt(), $shopToken->getRefreshToken());
        $this->getOwnerSession()->setToken((string) $ownerToken->getJwt(), $ownerToken->getRefreshToken());
    }

    /**
     * @param array $response
     * @param string $name
     * @param string $refreshName
     *
     * @return Token
     *
     * @throws RefreshTokenException
     */
    protected function getFirebaseTokenFromResponse(
        array $response,
              $name,
              $refreshName
    ) {
        if ($response && true === $response['status']) {
            return new Token(
                $response['body'][$name],
                $response['body'][$refreshName]
            );
        }

        $errorMsg = isset($response['body']['message']) ?
            $response['body']['message'] :
            '';

        throw new RefreshTokenException('Unable to refresh firebase ' . $name . ' token : ' . $response['httpCode'] . ' ' . print_r($errorMsg, true));
    }
}
