<?php

function upgrade_module_2_3_3()
{
    $result = true;

    $module = Module::getInstanceByName('ps_accounts');

    $hooks = [
        'hookActionObjectProductAddAfter',
        'hookActionObjectProductUpdateAfter',
    ];

    $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_type_sync` ADD `id_shop` INT(10) NOT NULL AFTER `offset`; ';
    $sql .= 'ALTER TABLE `' . _DB_PREFIX_ . 'accounts_type_sync` ADD `full_sync_finished` TINYINT(1) NOT NULL DEFAULT 0 AFTER `lang_iso`; ';

    foreach ($hooks as $hook) {
        $result &= $module->registerHook($hook);
    }

    return Db::getInstance()->execute($sql) && $result;
}
