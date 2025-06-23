<?php

use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentitiesCommand;
use PrestaShop\Module\PsAccounts\Account\Command\MigrateIdentitiesCommand;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

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

        /** @var ConfigurationRepository $configurationRepository */
        $configurationRepository = $module->getService(ConfigurationRepository::class);

        $shopExists = $configurationRepository->getShopUuid();

        if ($shopExists) {
            $commandBus->handle(new MigrateIdentitiesCommand());
        } else {
            // TODO: how to verify if a shop is unintentionally dissociated?
            $commandBus->handle(new CreateIdentitiesCommand());
        }

        // TODO: catch migration error
        // TODO: replay migration on reset

        $configurationRepository->updateLastUpgrade(\Ps_accounts::VERSION);

        /* @phpstan-ignore-next-line */
    } catch (\Throwable $e) {
    } catch (\Exception $e) {
        Logger::getInstance()->error('error during upgrade : ' . $e->getMessage());
    }

    return true;
}
