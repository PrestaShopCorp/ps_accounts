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
function upgrade_module_8_0_15($module)
{
    /** @var ConfigurationRepository $configurationRepository */
    $configurationRepository = $module->getService(ConfigurationRepository::class);
    $configurationRepository->fixMultiShopConfig(true);

    return true;
}
