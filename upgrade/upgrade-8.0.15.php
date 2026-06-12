<?php

use PrestaShop\Module\PsAccounts\Log\Logger;

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 * @throws Throwable
 */
function upgrade_module_8_0_15($module)
{
    require_once __DIR__ . '/helpers.php';

    upgrade_8_0_15_fix_multishop_config();
    migrate_or_create_identities_v8($module, '8.0.15');

    return true;
}

/**
 * Inline replication of ConfigurationRepository::fixMultiShopConfig(true) + normalizeGroupRows
 * using raw SQL only — no dependency on any class instance. This guarantees the full 8.0.15
 * logic runs on PS9 zip upgrades, where ConfigurationRepository in memory is the stale
 * pre-8.0.15 version (no normalizeGroupRows).
 *
 * @return void
 */
function upgrade_8_0_15_fix_multishop_config()
{
    // Full list of ConfigurationKeys::cases() string values as of 8.0.15.
    // Hardcoded to avoid depending on ConfigurationKeys (may be stale on PS9 zip upgrade).
    $allKeys = "'PSX_UUID_V4','PS_ACCOUNTS_LOGIN_ENABLED','PS_ACCOUNTS_OAUTH2_CLIENT_ID',"
        . "'PS_ACCOUNTS_OAUTH2_CLIENT_SECRET','PS_ACCOUNTS_ACCESS_TOKEN','PS_ACCOUNTS_LAST_UPGRADE',"
        . "'PS_ACCOUNTS_SHOP_PROOF','PS_ACCOUNTS_SHOP_STATUS','PS_ACCOUNTS_VALIDATION_LEEWAY',"
        . "'PS_ACCOUNTS_TOKEN_EXPIRATION_LEEWAY','PS_ACCOUNTS_FIREBASE_ID_TOKEN',"
        . "'PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN','PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN',"
        . "'PS_ACCOUNTS_USER_FIREBASE_REFRESH_TOKEN','PS_ACCOUNTS_USER_FIREBASE_UUID',"
        . "'PS_ACCOUNTS_FIREBASE_EMAIL','PS_ACCOUNTS_EMPLOYEE_ID','PS_CHECKOUT_SHOP_UUID_V4',"
        . "'PS_PSX_FIREBASE_ID_TOKEN','PS_PSX_FIREBASE_REFRESH_TOKEN',"
        . "'PS_PSX_FIREBASE_REFRESH_DATE','PS_PSX_FIREBASE_EMAIL'";

    try {
        $db = \Db::getInstance();

        // Replicate Configuration::isMultishopActive()
        $isMultishopActive = $db->getValue(
            'SELECT value FROM `' . _DB_PREFIX_ . 'configuration` WHERE name = "PS_MULTISHOP_FEATURE_ACTIVE"'
        ) && ($db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'shop') > 1);

        // Replicate Configuration::getMainShopId()
        $defaultShopId = (int) $db->getValue(
            'SELECT value FROM ' . _DB_PREFIX_ . "configuration WHERE name = 'PS_SHOP_DEFAULT'"
        );

        $shopIdCondition = $isMultishopActive ? $defaultShopId : 'NULL';

        // Align id_shop and force id_shop_group = NULL for all module keys
        $db->query(
            'UPDATE ' . _DB_PREFIX_ . 'configuration SET id_shop = ' . $shopIdCondition . ', id_shop_group = NULL'
            . ' WHERE name IN(' . $allKeys . ')'
            . ' AND id_shop ' . ($isMultishopActive ? 'IS NULL' : '= ' . $defaultShopId)
        );

        if ($isMultishopActive) {
            // Copy group-row value to the NULL row when the group row is more recent
            $db->query(
                'UPDATE ' . _DB_PREFIX_ . 'configuration n'
                . ' INNER JOIN ' . _DB_PREFIX_ . 'configuration g'
                . ' ON g.name = n.name AND g.id_shop = n.id_shop AND g.id_shop_group IS NOT NULL'
                . ' SET n.value = g.value'
                . ' WHERE n.name IN(' . $allKeys . ')'
                . ' AND n.id_shop_group IS NULL'
                . ' AND g.date_upd > n.date_upd'
            );

            // Drop group rows that have a NULL counterpart (NULL row is now authoritative)
            $db->query(
                'DELETE g FROM ' . _DB_PREFIX_ . 'configuration g'
                . ' INNER JOIN ' . _DB_PREFIX_ . 'configuration n'
                . ' ON n.name = g.name AND n.id_shop = g.id_shop AND n.id_shop_group IS NULL'
                . ' WHERE g.name IN(' . $allKeys . ')'
                . ' AND g.id_shop_group IS NOT NULL'
            );

            // Promote remaining orphan group rows (no NULL counterpart) to NULL
            $db->query(
                'UPDATE ' . _DB_PREFIX_ . 'configuration'
                . ' SET id_shop_group = NULL'
                . ' WHERE name IN(' . $allKeys . ')'
                . ' AND id_shop_group IS NOT NULL'
            );
        }
    } catch (\Exception $e) {
        Logger::getInstance()->error('error during upgrade upgrade_8_0_15_fix_multishop_config : ' . $e);
    } catch (\Throwable $e) {
        Logger::getInstance()->error('error during upgrade upgrade_8_0_15_fix_multishop_config : ' . $e);
    }
}
