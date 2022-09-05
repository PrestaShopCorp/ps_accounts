<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_6_0_0($module)
{
    // create employee_account table
    $installer = new PrestaShop\Module\PsAccounts\Module\Install($module, Db::getInstance());
    $installer->installDatabaseTables();

    return true;
}
