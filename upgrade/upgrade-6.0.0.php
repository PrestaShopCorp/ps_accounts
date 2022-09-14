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
    (new PrestaShop\Module\PsAccounts\Module\Install($module, Db::getInstance()))
        ->runMigration('create_employee_account_table');

    return true;
}
