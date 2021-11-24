<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_5_2_0($module)
{
    $module->addCustomHooks($module->getCustomHooks());
    $module->registerHook($module->getHookToInstall());

    return true;
}
