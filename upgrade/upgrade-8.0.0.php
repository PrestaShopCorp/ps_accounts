<?php

use PrestaShop\Module\PsAccounts\Account\Command\MigrateOrCreateIdentitiesV8Command;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
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
    require __DIR__ . '/../src/enforce_autoload.php';

    $e = null;

    try {
        $module->unregisterHook('actionObjectShopDeleteBefore');
        $module->unregisterHook('actionObjectShopUpdateAfter');
        $module->unregisterHook('actionObjectShopUrlUpdateAfter');
        $module->unregisterHook('actionShopAccountLinkAfter');
        $module->unregisterHook('actionShopAccountUnlinkAfter');
        $module->unregisterHook('displayAccountUpdateWarning');
        // $module->unregisterHook('displayBackOfficeHeader');

        $module->registerHook($module->getHooksToRegister());

        $tabId = \Tab::getIdFromClassName('AdminDebugPsAccounts');
        if ($tabId) {
            $tab = new \Tab($tabId);
            $tab->delete();
        }

        $installer = new PrestaShop\Module\PsAccounts\Module\Install($module, Db::getInstance());
        $installer->installInMenu();

        /** @var CommandBus $commandBus */
        $commandBus = $module->getService(CommandBus::class);

        $commandBus->handle(new MigrateOrCreateIdentitiesV8Command('ps_accounts'));
    } catch (\Exception $e) {
    } catch (\Throwable $e) {
    }

    if ($e) {
        Logger::getInstance()->error('error during upgrade : ' . $e);
    }

    return true;
}
