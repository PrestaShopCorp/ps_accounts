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

use PrestaShop\Module\PsAccounts\Account\Session\Session;
use PrestaShop\Module\PsAccounts\Account\Session\SessionInterface;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;

abstract class FirebaseSession extends Session implements SessionInterface
{
    /**
     * @return AccountsClient
     *
     * @throws \Exception
     */
    protected function getAccountsClient()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        return $module->getService(AccountsClient::class);
    }

    /**
     * @param Token $token
     *
     * @return Token
     *
     * @throws RefreshTokenException
     */
    protected function refreshFirebaseTokens($token)
    {
        if ($token->getJwt() instanceof NullToken) {
            throw new RefreshTokenException('No valid access token.');
        }

        $response = $this->getAccountsClient()->firebaseTokens($token);

        $type = $this instanceof ShopSession ? 'shop' : 'user';

        return $this->getFirebaseTokenFromResponse($response, $type . 'Token', $type . 'RefreshToken');
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
