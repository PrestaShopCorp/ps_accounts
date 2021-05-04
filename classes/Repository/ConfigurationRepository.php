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

use PrestaShop\Module\PsAccounts\Adapter\Configuration;

class ConfigurationRepository
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * ConfigurationRepository constructor.
     *
     * @param Configuration|null $configuration
     */
    public function __construct(Configuration $configuration = null)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return int
     */
    public function getShopId()
    {
        return $this->configuration->getIdShop();
    }

    /**
     * @param int $shopId
     *
     * @return void
     */
    public function setShopId($shopId)
    {
        $this->configuration->setIdShop($shopId);
    }

    /**
     * @return string
     */
    public function getFirebaseIdToken()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN);
    }

    /**
     * @return string
     */
    public function getFirebaseRefreshToken()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN);
    }

    /**
     * @param string $idToken
     * @param string $refreshToken
     *
     * @return void
     */
    public function updateFirebaseIdAndRefreshTokens($idToken, $refreshToken)
    {
        if (false === $this->configuration->get(Configuration::PS_PSX_FIREBASE_ID_TOKEN)) {
            // FIXME: This to avoid mutual disconnect between ps_accounts & ps_checkout
            $this->configuration->set(Configuration::PS_PSX_FIREBASE_ID_TOKEN, $idToken);
            $this->configuration->set(Configuration::PS_PSX_FIREBASE_REFRESH_TOKEN, $refreshToken);
            $this->configuration->set(Configuration::PS_PSX_FIREBASE_REFRESH_DATE, date('Y-m-d H:i:s'));
        }
        $this->configuration->set(Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN, $idToken);
        $this->configuration->set(Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN, $refreshToken);
    }

    /**
     * Check if we have a refresh token.
     *
     * @return bool
     */
    public function hasFirebaseRefreshToken()
    {
        return !empty($this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN));
    }

    /**
     * @return string | null
     */
    public function getFirebaseEmail()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_EMAIL);
    }

    /**
     * @param string $email
     *
     * @return void
     */
    public function updateFirebaseEmail($email)
    {
        if (false === $this->configuration->get(Configuration::PS_PSX_FIREBASE_EMAIL)) {
            $this->configuration->set(Configuration::PS_PSX_FIREBASE_EMAIL, $email);
        }
        $this->configuration->set(Configuration::PS_ACCOUNTS_FIREBASE_EMAIL, $email);
    }

    /**
     * @return bool
     */
    public function firebaseEmailIsVerified()
    {
        return in_array(
            $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_EMAIL_IS_VERIFIED),
            ['1', 1, true]
        );
    }

    /**
     * @param bool $status
     *
     * @return void
     */
    public function updateFirebaseEmailIsVerified($status)
    {
        $this->configuration->set(
            Configuration::PS_ACCOUNTS_FIREBASE_EMAIL_IS_VERIFIED,
            (string) $status
        );
    }

    /**
     * @deprecated since v4.0.0
     *
     * @return string | null
     */
    public function getFirebaseLocalId()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_FIREBASE_LOCAL_ID);
    }

    /**
     * @deprecated sibce v4.0.0
     *
     * @param string $localId
     *
     * @return void
     */
    public function updateFirebaseLocalId($localId)
    {
        if (false === $this->configuration->get(Configuration::PS_PSX_FIREBASE_LOCAL_ID)) {
            $this->configuration->set(Configuration::PS_PSX_FIREBASE_LOCAL_ID, $localId);
        }
        $this->configuration->set(Configuration::PS_ACCOUNTS_FIREBASE_LOCAL_ID, $localId);
    }

    /**
     * @return string
     */
    public function getShopUuid()
    {
        return $this->configuration->get(Configuration::PSX_UUID_V4);
    }

    /**
     * @param string $uuid Firebase User UUID
     *
     * @return void
     */
    public function updateShopUuid($uuid)
    {
        if (false === $this->configuration->get(Configuration::PS_CHECKOUT_SHOP_UUID_V4)) {
            $this->configuration->set(Configuration::PS_CHECKOUT_SHOP_UUID_V4, $uuid);
        }
        $this->configuration->set(Configuration::PSX_UUID_V4, $uuid);
    }

    /**
     * @return string
     */
    public function getAccountsRsaPrivateKey()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_RSA_PRIVATE_KEY);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function updateAccountsRsaPrivateKey($key)
    {
        $this->configuration->set(Configuration::PS_ACCOUNTS_RSA_PRIVATE_KEY, $key);
    }

    /**
     * @return string
     */
    public function getAccountsRsaPublicKey()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_RSA_PUBLIC_KEY);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function updateAccountsRsaPublicKey($key)
    {
        $this->configuration->set(Configuration::PS_ACCOUNTS_RSA_PUBLIC_KEY, $key);
    }

    /**
     * @return string
     */
    public function getAccountsRsaSignData()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_RSA_SIGN_DATA);
    }

    /**
     * @param string $signData
     *
     * @return void
     */
    public function updateAccountsRsaSignData($signData)
    {
        $this->configuration->set(Configuration::PS_ACCOUNTS_RSA_SIGN_DATA, $signData);
    }

    /**
     * @return bool
     */
    public function sslEnabled()
    {
        return true == $this->configuration->get('PS_SSL_ENABLED');
    }
}
