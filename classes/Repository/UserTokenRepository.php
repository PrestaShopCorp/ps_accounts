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
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;

/**
 * Class UserTokenRepository
 */
class UserTokenRepository extends AbstractTokenRepository
{
    const TOKEN_TYPE = 'user';
    const TOKEN_KEY = 'idToken';
    const REFRESH_TOKEN_KEY = 'refreshToken';

    /**
     * @return SsoClient
     *
     * @throws \Exception
     */
    protected function client()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        return $module->getService(SsoClient::class);
    }

    /**
     * @return Token|null
     */
    public function getToken()
    {
        return $this->parseToken($this->configuration->getUserFirebaseIdToken());
    }

    /**
     * @return string
     */
    public function getTokenUuid()
    {
        return $this->configuration->getUserFirebaseUuid();
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->configuration->getUserFirebaseRefreshToken();
    }

    /**
     * @return void
     */
    public function cleanupCredentials()
    {
        $this->configuration->updateUserFirebaseUuid('');
        $this->configuration->updateUserFirebaseIdToken('');
        $this->configuration->updateUserFirebaseRefreshToken('');
        $this->configuration->updateFirebaseEmail('');
        //$this->configuration->updateFirebaseEmailIsVerified(false);
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

        $uuid = $token->claims()->get('user_id');
        $this->configuration->updateUserFirebaseUuid($uuid);
        $this->configuration->updateUserFirebaseIdToken($idToken);
        $this->configuration->updateUserFirebaseRefreshToken($refreshToken);

        $this->configuration->updateFirebaseEmail($token->claims()->get('email'));
    }

    /**
     * @return string
     */
    public function getTokenEmail()
    {
        return $this->configuration->getFirebaseEmail();
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function getTokenEmailVerified()
    {
        $token = $this->getToken();

        // FIXME : just query sso api and don't refresh token everytime
        if (null !== $token && !$token->claims()->get('email_verified')) {
            try {
                $token = $this->getOrRefreshToken(true);
            } catch (RefreshTokenException $e) {
            }
        }

        return null !== $token && (bool) $token->claims()->get('email_verified');
    }
}
