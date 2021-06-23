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
    $moduleInstaller = $module->getService(\PrestaShop\Module\PsAccounts\Installer\Installer::class);

    // Ignore fail on ps_eventbus install
    $moduleInstaller->installModule('ps_eventbus');

    // Removed controller
    $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($this, Db::getInstance());
    $uninstaller->deleteAdminTab('AdminConfigureHmacPsAccounts');

    return true;
}
