<?php

function upgrade_module_3_2_0()
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

    $sqlQueries[] = 'DELETE FROM `' . _DB_PREFIX_ . 'accounts_sync`;';
    $sqlQueries[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_sync` CHANGE `job_id` `job_id` VARCHAR(200) NOT NULL;';
    $sqlQueries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'accounts_deleted_objects` (
    `type`       VARCHAR(50)      NOT NULL,
    `id_object`  INT(10) UNSIGNED NOT NULL,
    `id_shop`    INT(10) UNSIGNED NOT NULL,
    `created_at` DATETIME         NOT NULL,
    PRIMARY KEY (`type`, `id_object`, `id_shop`));';
    $sqlQueries[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_type_sync` ADD `id_shop` INT(10) NOT NULL AFTER `offset`; ';
    $sqlQueries[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_type_sync` ADD `full_sync_finished` TINYINT(1) NOT NULL DEFAULT 0 AFTER `lang_iso`; ';
    $sqlQueries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'accounts_incremental_sync`(
    `type` VARCHAR(50) NOT NULL,
    `id_object` INT(10) UNSIGNED NOT NULL,
    `id_shop` INT(10) UNSIGNED NOT NULL,
    `lang_iso`   VARCHAR(3),
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY( `type`, `id_object`, `id_shop`, `lang_iso`));';

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
