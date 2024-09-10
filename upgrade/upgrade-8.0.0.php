<?php
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
    /** @var \PrestaShop\Module\PsAccounts\Cqrs\CommandBus $commandBus */
    $commandBus = $module->getService(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);
    $commandBus->handle(new \PrestaShop\Module\PsAccounts\Account\Command\MultiCreateIdentityCommand([]));

    return true;
}
