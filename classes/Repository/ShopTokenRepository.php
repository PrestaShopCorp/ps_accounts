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
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;

class ShopTokenRepository
{
    /**
     * @var AccountsClient
     */
    private $accountsClient;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * ShopTokenService constructor.
     *
     * @param AccountsClient $accountsClient
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        AccountsClient $accountsClient,
        ConfigurationRepository $configuration
    ) {
        $this->accountsClient = $accountsClient;
        $this->configuration = $configuration;
    }

    /**
     * Get the user firebase token.
     *
     * @return Token|null
     *
     * @throws \Exception
     */
    public function getOrRefreshToken()
    {
        if (
            $this->configuration->hasFirebaseRefreshToken()
            && $this->isTokenExpired()
        ) {
            $refreshToken = $this->getRefreshToken();
            $this->configuration->updateFirebaseIdAndRefreshTokens(
                $this->refreshToken($refreshToken), $refreshToken
            );
        }

        return $this->getToken();
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->configuration->getFirebaseRefreshToken();
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
        //return $this->getToken()->claims()->get('user_id');
        return $this->configuration->getShopUuid();
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
     * @param $idToken
     * @param $refreshToken
     *
     * @return Token|null verified or refreshed token on success
     *
     * @throws \Exception
     */
    public function verifyToken($idToken, $refreshToken)
    {
        $response = $this->accountsClient->verifyToken($idToken);

        if ($response && true == $response['status']) {
            return $idToken;
        }

        return $this->refreshToken($refreshToken);
    }

    /**
     * @param string $refreshToken
     *
     * @return Token|null idToken
     *
     * @throws \Exception
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->accountsClient->refreshToken($refreshToken);

        if ($response && true == $response['status']) {
            return $this->parseToken($response['body']['token']);
        }
        throw new \Exception('Unable to refresh shop token : ' . $response['httpCode'] . ' ' . $response['body']['message']);
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

    /**
     * @return void
     */
    public function cleanupCredentials()
    {
        $this->configuration->updateShopUuid('');
        $this->configuration->updateFirebaseIdAndRefreshTokens('', '');
    }
}
