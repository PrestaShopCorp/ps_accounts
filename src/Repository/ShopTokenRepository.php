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

namespace PrestaShop\Module\PsAccounts\Repository;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;

/**
 * Class ShopTokenRepository
 */
class ShopTokenRepository extends AbstractTokenRepository
{
    public const TOKEN_TYPE = 'shop';
    protected const TOKEN_KEY = 'token';
    protected const REFRESH_TOKEN_KEY = 'refresh_token';

    /**
     * @return AccountsClient
     *
     * @throws \Exception
     */
    protected function client()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        return $module->getService(AccountsClient::class);
    }

    /**
     * @return Token|null
     */
    public function getToken()
    {
        return $this->parseToken($this->configuration->getFirebaseIdToken());
    }

    /**
     * @return string
     */
    public function getTokenUuid()
    {
        return $this->configuration->getShopUuid();
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->configuration->getFirebaseRefreshToken();
    }

    /**
     * @return void
     */
    public function cleanupCredentials()
    {
        $this->configuration->updateShopUuid('');
        $this->configuration->updateFirebaseIdAndRefreshTokens('', '');
    }

    /**
     * @param string $idToken
     * @param string $refreshToken
     *
     * @return void
     */
    public function updateCredentials($idToken, $refreshToken)
    {
        $token = (new Parser())->parse((string) $idToken);

        $this->configuration->updateShopUuid($token->getClaim('user_id'));
        $this->configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);
    }
}
