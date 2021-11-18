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
     * @return string|null
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
     * @return string|null
     */
    public function getEmployeeId()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_EMPLOYEE_ID);
    }

    /**
     * @param string $employeeId
     *
     * @return void
     */
    public function updateEmployeeId($employeeId)
    {
        $this->configuration->set(Configuration::PS_ACCOUNTS_EMPLOYEE_ID, $employeeId);
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
     * @return string|bool
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
        return true == $this->configuration->get('PS_SSL_ENABLED')
            || true == $this->configuration->get('PS_SSL_ENABLED_EVERYWHERE');
    }

    /**
     * @return mixed
     */
    public function getUserFirebaseUuid()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_USER_FIREBASE_UUID);
    }

    /**
     * @param string $uuid
     *
     * @return void
     */
    public function updateUserFirebaseUuid($uuid)
    {
        $this->configuration->set(Configuration::PS_ACCOUNTS_USER_FIREBASE_UUID, $uuid);
    }

    /**
     * @return mixed
     */
    public function getUserFirebaseIdToken()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN);
    }

    /**
     * @param string $idToken
     *
     * @return void
     */
    public function updateUserFirebaseIdToken($idToken)
    {
        $this->configuration->set(Configuration::PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN, $idToken);
    }

    /**
     * @return mixed
     */
    public function getUserFirebaseRefreshToken()
    {
        return $this->configuration->get(Configuration::PS_ACCOUNTS_USER_FIREBASE_REFRESH_TOKEN);
    }

    /**
     * @param string $refreshToken
     *
     * @return void
     */
    public function updateUserFirebaseRefreshToken($refreshToken)
    {
        $this->configuration->set(Configuration::PS_ACCOUNTS_USER_FIREBASE_REFRESH_TOKEN, $refreshToken);
    }

    /**
     *   Get shop who is defined as main in the prestashop
     *
     *   @return \Shop
     */
    public function getMainShop()
    {
        $mainShopId = \Db::getInstance()->getValue('SELECT value FROM ' . _DB_PREFIX_ . "configuration WHERE name = 'PS_SHOP_DEFAULT'");
        $shop = new \Shop((int) $mainShopId);

        return $shop;
    }

    /**
     * specify id_shop & id_shop_group for shop
     *
     * @return void
     */
    public function migrateToMultiShop()
    {
        $shop = $this->getMainShop();
        \Db::getInstance()->query(
            'UPDATE ' . _DB_PREFIX_ . 'configuration SET id_shop = ' . (int) $shop->id . ', id_shop_group = ' . (int) $shop->id_shop_group .
            " WHERE (name like 'PS_ACCOUNTS_%' OR name = 'PSX_UUID_V4')" .
            ' AND id_shop IS NULL AND id_shop_group IS NULL;'
        );
    }

    /**
     * nullify id_shop & id_shop_group for shop
     *
     * @return void
     */
    public function migrateToSingleShop()
    {
        $shop = $this->getMainShop();
        \Db::getInstance()->query(
            'UPDATE ' . _DB_PREFIX_ . 'configuration SET id_shop = NULL, id_shop_group = NULL' .
            " WHERE (name like 'PS_ACCOUNTS_%' OR name = 'PSX_UUID_V4')" .
            ' AND id_shop = ' . (int) $shop->id . ' AND id_shop_group = ' . (int) $shop->id_shop_group . ';'
        );
    }
}
