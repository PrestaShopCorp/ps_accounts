<?php

use PrestaShop\Module\PsAccounts\Log\Logger;

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 * @throws Throwable
 */
function upgrade_module_8_0_0($module)
{
    require_once __DIR__ . '/helpers.php';

    try {
        $module->unregisterHook('actionObjectShopDeleteBefore');
        $module->unregisterHook('actionObjectShopUpdateAfter');
        $module->unregisterHook('actionObjectShopUrlUpdateAfter');
        $module->unregisterHook('actionShopAccountLinkAfter');
        $module->unregisterHook('actionShopAccountUnlinkAfter');
        $module->unregisterHook('displayAccountUpdateWarning');

        $module->registerHook($module->getHooksToRegister());

        $tabId = \Tab::getIdFromClassName('AdminDebugPsAccounts');
        if ($tabId) {
            $tab = new \Tab($tabId);
            $tab->delete();
        }

        $installer = new PrestaShop\Module\PsAccounts\Module\Install($module, Db::getInstance());
        $installer->installInMenu();
    } catch (\Exception $e) {
        Logger::getInstance()->error('error during upgrade : ' . $e);
    } catch (\Throwable $e) {
        Logger::getInstance()->error('error during upgrade : ' . $e);
    }

    migrate_or_create_identities_v8($module);

    return true;
}
