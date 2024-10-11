<?php
use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentitiesCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UpgradeModulesCommand;
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
    // 1- force load autoload
    // 2- manage ou own cache
    try {
        /** @var CommandBus $commandBus */
        $commandBus = $module->getService(CommandBus::class);
        // FIXME: async guzzle requests
        // FIXME: curl version of those calls
        $commandBus->handle(new CreateIdentitiesCommand());
        $commandBus->handle(new UpgradeModulesCommand());
    } catch (\Exception $e) {
        Logger::getInstance()>error('error during upgrade : ' . $e->getMessage());
    }

    return true;
}


