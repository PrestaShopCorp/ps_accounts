<?php

use PrestaShop\Module\PsAccounts\Account\Command\MigrateShopIdentitiesCommand;
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

        $commandBus->handle(new MigrateShopIdentitiesCommand($module->getParameter('ps_accounts.token_audience')));

        /* @phpstan-ignore-next-line */
    } catch (\Throwable $e) {
    } catch (\Exception $e) {
        Logger::getInstance()->error('error during upgrade : ' . $e->getMessage());
    }

    return true;
}
