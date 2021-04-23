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
function upgrade_module_3_2_1()
{
    /** @var Ps_accounts $module */
    $module = Module::getInstanceByName('ps_accounts');

    $hooks = [
        'actionObjectShopUrlUpdateAfter',
        'actionObjectProductDeleteAfter',
        'actionObjectCategoryDeleteAfter',
        'actionObjectProductAddAfter',
        'actionObjectProductUpdateAfter',
        'actionObjectCartAddAfter',
        'actionObjectCartUpdateAfter',
        'actionObjectOrderAddAfter',
        'actionObjectOrderUpdateAfter',
        'actionObjectCategoryAddAfter',
        'actionObjectCategoryUpdateAfter',
    ];

    $sqlQueries = [];

    $sqlQueries[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'accounts_sync`';
    $sqlQueries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'accounts_type_sync`
    (
        `type`               VARCHAR(50)      NOT NULL,
        `offset`             INT(10) UNSIGNED NOT NULL DEFAULT 0,
        `id_shop`            INT(10) UNSIGNED NOT NULL,
        `lang_iso`           VARCHAR(3),
        `full_sync_finished` TINYINT(1)       NOT NULL DEFAULT 0,
        `last_sync_date`     DATETIME         NOT NULL
    ) ENGINE = ENGINE_TYPE
      DEFAULT CHARSET = utf8;';
    $sqlQueries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'accounts_sync`
    (
        `job_id`     VARCHAR(200) NOT NULL,
        `created_at` DATETIME     NOT NULL
    ) ENGINE = ENGINE_TYPE
      DEFAULT CHARSET = utf8;';
    $sqlQueries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'accounts_deleted_objects`
    (
        `type`       VARCHAR(50)      NOT NULL,
        `id_object`  INT(10) UNSIGNED NOT NULL,
        `id_shop`    INT(10) UNSIGNED NOT NULL,
        `created_at` DATETIME         NOT NULL,
        PRIMARY KEY (`type`, `id_object`, `id_shop`)
    ) ENGINE = ENGINE_TYPE
      DEFAULT CHARSET = utf8;';
    $sqlQueries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'accounts_incremental_sync`
    (
        `type`       VARCHAR(50)      NOT NULL,
        `id_object`  INT(10) UNSIGNED NOT NULL,
        `id_shop`    INT(10) UNSIGNED NOT NULL,
        `lang_iso`   VARCHAR(3),
        `created_at` DATETIME         NOT NULL,
        PRIMARY KEY (`type`, `id_object`, `id_shop`, `lang_iso`)
    ) ENGINE = ENGINE_TYPE
      DEFAULT CHARSET = utf8;';

    $sqlQueries[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_type_sync` ADD `id_shop` INT(10) NOT NULL AFTER `offset`; ';
    $sqlQueries[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_type_sync` ADD `lang_iso` VARCHAR(3) AFTER `id_shop`; ';
    $sqlQueries[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_type_sync` ADD `full_sync_finished` TINYINT(1) NOT NULL DEFAULT 0 AFTER `lang_iso`; ';
    $sqlQueries[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_type_sync` ADD `last_sync_date` DATETIME NOT NULL AFTER `full_sync_finished`; ';

    foreach ($sqlQueries as $sqlQuery) {
        try {
            Db::getInstance()->execute($sqlQuery);
        } catch (Exception $exception) {
        }
    }

    foreach ($hooks as $hook) {
        $module->registerHook($hook);
    }

    return true;
}
