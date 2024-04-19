<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_5_0_0($module)
{
    /** @var \PrestaShop\Module\PsAccounts\Module\Uninstall $uninstaller */
    $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($module, Db::getInstance());

    // Removed controller
    $uninstaller->deleteAdminTab('AdminConfigureHmacPsAccounts');

    return true;
}
