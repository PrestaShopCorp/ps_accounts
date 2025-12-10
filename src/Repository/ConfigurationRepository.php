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
use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;

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
     *
     * @throws \Exception
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
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_FIREBASE_ID_TOKEN);
    }

    /**
     * @return string
     */
    public function getFirebaseRefreshToken()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN);
    }

    /**
     * @param string $idToken
     * @param string $refreshToken
     *
     * @return void
     */
    public function updateFirebaseIdAndRefreshTokens($idToken, $refreshToken)
    {
        if (false === $this->configuration->get(ConfigurationKeys::PS_PSX_FIREBASE_ID_TOKEN)) {
            // FIXME: This to avoid mutual disconnect between ps_accounts & ps_checkout
            $this->configuration->set(ConfigurationKeys::PS_PSX_FIREBASE_ID_TOKEN, $idToken);
            $this->configuration->set(ConfigurationKeys::PS_PSX_FIREBASE_REFRESH_TOKEN, $refreshToken);
            $this->configuration->set(ConfigurationKeys::PS_PSX_FIREBASE_REFRESH_DATE, date('Y-m-d H:i:s'));
        }
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_FIREBASE_ID_TOKEN, $idToken);
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN, $refreshToken);
    }

    /**
     * Owner Email
     *
     * @return string|null
     *
     * @deprecated
     */
    public function getFirebaseEmail()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_FIREBASE_EMAIL);
    }

    /**
     * @param string $email
     *
     * @return void
     */
    public function updateFirebaseEmail($email)
    {
        if (false === $this->configuration->get(ConfigurationKeys::PS_PSX_FIREBASE_EMAIL)) {
            $this->configuration->set(ConfigurationKeys::PS_PSX_FIREBASE_EMAIL, $email);
        }
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_FIREBASE_EMAIL, $email);
    }

    /**
     * @return mixed
     */
    public function getUserFirebaseUuid()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_USER_FIREBASE_UUID);
    }

    /**
     * @param string $uuid
     *
     * @return void
     */
    public function updateUserFirebaseUuid($uuid)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_USER_FIREBASE_UUID, $uuid);
    }

    /**
     * @return string|null
     */
    public function getEmployeeId()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_EMPLOYEE_ID);
    }

    /**
     * @param string $employeeId
     *
     * @return void
     */
    public function updateEmployeeId($employeeId)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_EMPLOYEE_ID, $employeeId);
    }

    /**
     * @return string
     */
    public function getShopUuid()
    {
        return $this->configuration->get(ConfigurationKeys::PSX_UUID_V4);
    }

    /**
     * @return \DateTime|null
     */
    public function getShopUuidDateUpd()
    {
        return $this->configuration->getDateUpd(ConfigurationKeys::PSX_UUID_V4);
    }

    /**
     * @param string $uuid Firebase User UUID
     *
     * @return void
     */
    public function updateShopUuid($uuid)
    {
        if (false === $this->configuration->get(ConfigurationKeys::PS_CHECKOUT_SHOP_UUID_V4)) {
            $this->configuration->set(ConfigurationKeys::PS_CHECKOUT_SHOP_UUID_V4, $uuid);
        }
        $this->configuration->set(ConfigurationKeys::PSX_UUID_V4, $uuid);
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
    public function getUserFirebaseIdToken()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN);
    }

    /**
     * @return mixed
     */
    public function getUserFirebaseRefreshToken()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_USER_FIREBASE_REFRESH_TOKEN);
    }

    /**
     * @param string $idToken
     *
     * @return void
     */
    public function updateUserFirebaseIdToken($idToken)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN, $idToken);
    }

    /**
     * @param string $idToken
     * @param string $refreshToken
     *
     * @return void
     */
    public function updateUserFirebaseIdAndRefreshToken($idToken, $refreshToken)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN, $idToken);
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_USER_FIREBASE_REFRESH_TOKEN, $refreshToken);
    }

    /**
     *   Get shop who is defined as main in the prestashop
     *
     *   @return \Shop
     */
    public function getMainShop()
    {
        $mainShopId = $this->getMainShopId();
        $shop = new \Shop((int) $mainShopId);

        return $shop;
    }

    /**
     * @return int
     */
    public function getMainShopId()
    {
        return (int) \Db::getInstance()->getValue('SELECT value FROM ' . _DB_PREFIX_ . "configuration WHERE name = 'PS_SHOP_DEFAULT'");
    }

    /**
     * @return bool
     */
    public function getLoginEnabled()
    {
        return (bool) $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_LOGIN_ENABLED);
    }

    /**
     * @param bool $enabled
     *
     * @return void
     */
    public function updateLoginEnabled($enabled)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_LOGIN_ENABLED, (string) $enabled);
    }

    /**
     * @return mixed
     */
    public function getOauth2ClientId()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_OAUTH2_CLIENT_ID);
    }

    /**
     * @param string $clientId
     *
     * @return void
     */
    public function updateOauth2ClientId($clientId)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_OAUTH2_CLIENT_ID, $clientId);
    }

    /**
     * @return mixed
     */
    public function getOauth2ClientSecret()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_OAUTH2_CLIENT_SECRET);
    }

    /**
     * @param string $secret
     *
     * @return void
     */
    public function updateOauth2ClientSecret($secret)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_OAUTH2_CLIENT_SECRET, $secret);
    }

    /**
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_ACCESS_TOKEN);
    }

    /**
     * @param string $accessToken
     *
     * @return void
     */
    public function updateAccessToken($accessToken)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_ACCESS_TOKEN, $accessToken);
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function fixMultiShopConfig()
    {
        if ($this->isMultishopActive()) {
            $this->migrateToMultiShop();
        } else {
            $this->migrateToSingleShop();
        }
    }

    /**
     * is multi-shop active "right now"
     *
     * @return bool
     */
    public function isMultishopActive()
    {
        //return \Shop::isFeatureActive();
        return $this->configuration->isMultishopActive();
    }

    /**
     * @param string $upgrade
     *
     * @return void
     */
    public function updateLastUpgrade($upgrade)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_LAST_UPGRADE, $upgrade);
    }

    /**
     * @param bool $cached
     *
     * @return string
     */
    public function getLastUpgrade($cached = true)
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_LAST_UPGRADE, false, $cached) ?: '0';
    }

    /**
     * @return string|null
     */
    public function getUnlinkedOnError()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_UNLINKED_ON_ERROR);
    }

    /**
     * @param string|null $error
     *
     * @return void
     */
    public function updateUnlinkedOnError($error)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_UNLINKED_ON_ERROR, $error);
    }

    /**
     * @return string|null
     */
    public function getShopProof()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_SHOP_PROOF);
    }

    /**
     * @param string|null $proof
     *
     * @return void
     */
    public function updateShopProof($proof)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_SHOP_PROOF, $proof);
    }

    /**
     * @return string|null
     */
    public function getCachedShopStatus()
    {
        return $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_CACHED_SHOP_STATUS, false, false);
    }

    /**
     * @return \DateTime|null
     */
    public function getCachedShopStatusDateUpd()
    {
        return $this->configuration->getDateUpd(ConfigurationKeys::PS_ACCOUNTS_CACHED_SHOP_STATUS);
    }

    /**
     * @param string|null $status
     *
     * @return void
     */
    public function updateCachedShopStatus($status)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_CACHED_SHOP_STATUS, $status);
    }

    /**
     * @return int|null
     */
    public function getValidationLeeway()
    {
        $leeway = $this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_VALIDATION_LEEWAY);
        if (!empty($leeway) || $leeway === '0') {
            return (int) $leeway;
        }

        return null;
    }

    /**
     * @param int $leeway
     *
     * @return void
     */
    public function updateValidationLeeway($leeway)
    {
        $this->configuration->set(ConfigurationKeys::PS_ACCOUNTS_VALIDATION_LEEWAY, (string) $leeway);
    }

    /**
     * specify id_shop & id_shop_group for shop
     *
     * @return void
     *
     * @throws \PrestaShopDatabaseException
     */
    protected function migrateToMultiShop()
    {
        $shop = $this->getMainShop();
        \Db::getInstance()->query(
            'UPDATE ' . _DB_PREFIX_ . 'configuration SET id_shop = ' . (int) $shop->id . ', id_shop_group = ' . (int) $shop->id_shop_group .
            " WHERE name IN('" . join("','", array_values(ConfigurationKeys::cases())) . "')" .
            ' AND id_shop IS NULL AND id_shop_group IS NULL;'
        );
    }

    /**
     * nullify id_shop & id_shop_group for shop
     *
     * @return void
     *
     * @throws \PrestaShopDatabaseException
     */
    protected function migrateToSingleShop()
    {
        $shop = $this->getMainShop();
        \Db::getInstance()->query(
            'UPDATE ' . _DB_PREFIX_ . 'configuration SET id_shop = NULL, id_shop_group = NULL' .
            " WHERE name IN('" . join("','", array_values(ConfigurationKeys::cases())) . "')" .
            ' AND id_shop = ' . (int) $shop->id . ';'
        );
    }
}
