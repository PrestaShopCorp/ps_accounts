<?php
use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentitiesCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UpgradeModulesCommand;
use PrestaShop\Module\PsAccounts\Account\Command\VerifyAuthenticitiesCommand;
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

    try {
        /** @var CommandBus $commandBus */
        $commandBus = $module->getService(CommandBus::class);
        // FIXME: async guzzle requests
        // FIXME: curl version of those calls
        $commandBus->handle(new CreateIdentitiesCommand());
        $commandBus->handle(new VerifyAuthenticitiesCommand());
        $commandBus->handle(new UpgradeModulesCommand());
    } catch (\Exception $e) {
        Logger::getInstance()>error('error during upgrade : ' . $e->getMessage());
    }

    return true;
}


