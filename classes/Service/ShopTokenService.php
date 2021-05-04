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

namespace PrestaShop\Module\PsAccounts\Service;

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Api\Client\FirebaseClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ShopTokenService
{
    /**
     * @var FirebaseClient
     */
    private $firebaseClient;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * ShopTokenService constructor.
     *
     * @param FirebaseClient $firebaseClient
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        FirebaseClient $firebaseClient,
        ConfigurationRepository $configuration
    ) {
        $this->firebaseClient = $firebaseClient;
        $this->configuration = $configuration;
    }

    /**
     * @see https://firebase.google.com/docs/reference/rest/auth Firebase documentation
     *
     * @param string $customToken
     *
     * @return bool
     */
    public function exchangeCustomTokenForIdAndRefreshToken($customToken)
    {
        $response = $this->firebaseClient->signInWithCustomToken($customToken);

        if ($response && true === $response['status']) {
            $uid = (new Parser())->parse((string) $customToken)->getClaim('uid');

            $this->configuration->updateShopUuid($uid);

            $this->configuration->updateFirebaseIdAndRefreshTokens(
                $response['body']['idToken'],
                $response['body']['refreshToken']
            );

            return true;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function refreshToken()
    {
        $response = $this->firebaseClient->exchangeRefreshTokenForIdToken(
            $this->configuration->getFirebaseRefreshToken()
        );

        if ($response && true === $response['status']) {
            $this->configuration->updateFirebaseIdAndRefreshTokens(
                $response['body']['id_token'],
                $response['body']['refresh_token']
            );

            return true;
        }

        return false;
    }

    /**
     * Get the user firebase token.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getOrRefreshToken()
    {
        if (
            $this->configuration->hasFirebaseRefreshToken()
            && $this->isTokenExpired()
        ) {
            $this->refreshToken();
        }

        return $this->configuration->getFirebaseIdToken();
    }

    /**
     * @return string|null
     */
    public function getRefreshToken()
    {
        return $this->configuration->getFirebaseRefreshToken() ?: null;
    }

    /**
     * @return string|null
     */
    public function getToken()
    {
        return $this->configuration->getFirebaseIdToken() ?: null;
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isTokenExpired()
    {
        // iat, exp
        $token = (new Parser())->parse($this->configuration->getFirebaseIdToken());

        return $token->isExpired(new \DateTime());
    }
}
