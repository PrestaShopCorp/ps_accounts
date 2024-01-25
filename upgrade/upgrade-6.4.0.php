<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_6_4_0($module)
{
    $module->unregisterHook('actionAdminControllerInitBefore');
    $module->registerHook($module->getHooksToRegister());

    // FIXME: this wont prevent from re-implanting override on reset of module
    $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($module, Db::getInstance());
    $uninstaller->deleteAdminTab('AdminLogin');

    $installer = new PrestaShop\Module\PsAccounts\Module\Install($module, Db::getInstance());
    $installer->installInMenu();

    return true;
}
