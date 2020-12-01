<?php

function upgrade_module_2_11_0()
{
    $module = Module::getInstanceByName('ps_accounts');

    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'accounts_deleted_objects` (
    `type`       VARCHAR(50)      NOT NULL,
    `id_object`  INT(10) UNSIGNED NOT NULL,
    `id_shop`    INT(10) UNSIGNED NOT NULL,
    `created_at` DATETIME         NOT NULL,
    PRIMARY KEY (`type`, `id_object`, `id_shop`)
    );';

    return Db::getInstance()->execute($sql)
        && $module->registerHook('actionObjectProductDeleteAfter')
        && $module->registerHook('actionObjectCategoryDeleteAfter');
}
