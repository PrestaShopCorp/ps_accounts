<?php

use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentitiesCommand;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;

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
    /** @var CommandBus $commandBus */
    $commandBus = $module->getService(CommandBus::class);
    $commandBus->handle(new CreateIdentitiesCommand());

    return true;
}
