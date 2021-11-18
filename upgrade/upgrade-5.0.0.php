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
    /** @var \PrestaShop\Module\PsAccounts\Installer\Installer $moduleInstaller */
    $moduleInstaller = $module->getService(\PrestaShop\Module\PsAccounts\Installer\Installer::class);

    // Ignore fail on ps_eventbus install
    $moduleInstaller->installModule('ps_eventbus');

    $module->registerHook($module->getHookToInstall());

    /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $psAccountService */
    $psAccountService = $module->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);
    $psAccountService->autoReonboardOnV5();

    /** @var \PrestaShop\Module\PsAccounts\Module\Uninstall $uninstaller */
    $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($module, Db::getInstance());

    // Removed controller
    $uninstaller->deleteAdminTab('AdminConfigureHmacPsAccounts');

    return true;
}
