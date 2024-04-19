<?php
/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 * @throws Throwable
 */
function upgrade_module_7_0_0($module)
{
    foreach ([
                 //'displayBackOfficeHeader',
                 //'actionAdminLoginControllerSetMedia',
                 'actionAdminControllerInitBefore',
                 'actionModuleInstallAfter',
             ] as $hook) {
        $module->unregisterHook($hook);
    }
    $module->addCustomHooks($module->getCustomHooks());
    $module->registerHook($module->getHooksToRegister());

    // FIXME: this wont prevent from re-implanting override on reset of module
    $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($module, Db::getInstance());
    $uninstaller->deleteAdminTab('AdminLogin');

    $installer = new PrestaShop\Module\PsAccounts\Module\Install($module, Db::getInstance());
    $installer->installInMenu();

    return true;
}
