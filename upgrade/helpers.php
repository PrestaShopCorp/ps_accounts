<?php

require __DIR__ . '/../src/enforce_autoload.php';

use PrestaShop\Module\PsAccounts\Account\Command\MigrateOrCreateIdentitiesV8Command;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

/**
 * @param Ps_accounts $module
 * @param string|null $version target version to register once migrated. Passed by the
 *                             upgrade script (always-fresh code) to avoid the stale
 *                             \Ps_accounts::VERSION const on PS9 zip upgrades. Null = const fallback.
 *
 * @return void
 *
 * @throws Exception
 * @throws Throwable
 */
function migrate_or_create_identities_v8($module, $version = null)
{
    try {
        /** @var CommandBus $commandBus */
        $commandBus = $module->getService(CommandBus::class);

        $commandBus->handle(
            (new MigrateOrCreateIdentitiesV8Command())
                ->withOrigin(AccountsService::ORIGIN_UPGRADE)
                ->withVersion($version)
        );
    } catch (\Exception $e) {
        Logger::getInstance()->error('error during upgrade : ' . $e);
    } catch (\Throwable $e) {
        Logger::getInstance()->error('error during upgrade : ' . $e);
    }
}
