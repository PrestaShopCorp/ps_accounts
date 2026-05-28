<?php

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 * @throws Throwable
 */
function upgrade_module_8_0_14($module)
{
    require_once __DIR__ . '/helpers.php';

    migrate_or_create_identities_v8($module);

    /** @var ConfigurationRepository $configurationRepository */
    $configurationRepository = $module->getService(ConfigurationRepository::class);
    $configurationRepository->fixMultiShopConfig(true);

    return true;
}
