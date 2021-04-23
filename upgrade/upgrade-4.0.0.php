<?php

/**
 * @param Ps_accounts $module
 */
function upgrade_module_4_0_0($module)
{
    $moduleInstaller = $module->getService(\PrestaShop\Module\PsAccounts\Installer\Installer::class);

    // Ignore fail on ps_eventbus install
    $moduleInstaller->installModule('ps_eventbus');

    return true;
}
