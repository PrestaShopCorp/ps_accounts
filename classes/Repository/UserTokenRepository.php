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
use Lcobucci\JWT\Token\InvalidTokenStructure;
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Log\Logger;

/**
 * Class PsAccountsService
 */
class UserTokenRepository
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * PsAccountsService constructor.
     *
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        ConfigurationRepository $configuration
    ) {
        $this->configuration = $configuration;
    }

    /**
     * @param bool $forceRefresh
     *
     * @return Token|null
     *
     * @throws \Exception
     */
    public function getOrRefreshToken($forceRefresh = false)
    {
        if (true === $forceRefresh || $this->isTokenExpired()) {
            $refreshToken = $this->getRefreshToken();
            if (is_string($refreshToken) && '' != $refreshToken) {
                try {
                    $this->updateCredentials(
                        (string) $this->refreshToken($refreshToken),
                        $refreshToken
                    );
                } catch (RefreshTokenException $e) {
                    Logger::getInstance()->debug($e);
                }
            }
        }

        return $this->getToken();
    }

    /**
     * @param string $idToken
     * @param string $refreshToken
     *
     * @return Token|null verified or refreshed token on success
     *
     * @throws RefreshTokenException
     */
    public function verifyToken($idToken, $refreshToken)
    {
        $response = $this->getSsoClient()->verifyToken($idToken);

        if ($response && true === $response['status']) {
            return $this->parseToken($idToken);
        }

        return $this->refreshToken($refreshToken);
    }

    /**
     * @param string $refreshToken
     *
     * @return Token|null idToken
     *
     * @throws RefreshTokenException
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->getSsoClient()->refreshToken($refreshToken);

        if ($response && true === $response['status']) {
            return $this->parseToken($response['body']['idToken']);
        }
        throw new RefreshTokenException('Unable to refresh user token : ' . $response['httpCode'] . ' ' . print_r($response['body']['message'], true));
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->configuration->getUserFirebaseRefreshToken();
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
        //return $this->getToken()->claims()->get('user_id');
        return $this->configuration->getUserFirebaseUuid();
    }

    /**
     * @return string
     */
    public function getTokenEmail()
    {
        //return $this->getToken()->claims()->get('user_id');
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

    /**
     * @param string $token
     *
     * @return Token|null
     */
    public function parseToken($token)
    {
        try {
            return (new Parser())->parse((string) $token);
        } catch (InvalidTokenStructure $e) {
            return null;
        }
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isTokenExpired()
    {
        // iat, exp
        $token = $this->getToken();

        return $token ? $token->isExpired(new \DateTime()) : true;
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
     * @return SsoClient
     *
     * @throws \Exception
     */
    private function getSsoClient()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        return $module->getService(SsoClient::class);
    }
}
