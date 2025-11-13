<?php

require __DIR__ . '/../src/enforce_autoload.php';

use PrestaShop\Module\PsAccounts\Account\Command\MigrateOrCreateIdentitiesV8Command;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

/**
 * @param Ps_accounts $module
 *
 * @return void
 *
 * @throws Exception
 * @throws Throwable
 */
function migrate_or_create_identities_v8($module)
{
    try {
        /** @var CommandBus $commandBus */
        $commandBus = $module->getService(CommandBus::class);

        $commandBus->handle(new MigrateOrCreateIdentitiesV8Command(
            AccountsService::ORIGIN_UPGRADE,
            'ps_accounts'
        ));
    } catch (\Exception $e) {
        Logger::getInstance()->error('error during upgrade : ' . $e);
    } catch (\Throwable $e) {
        Logger::getInstance()->error('error during upgrade : ' . $e);
    }
}
