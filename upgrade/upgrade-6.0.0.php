<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_6_0_0($module)
{
    $module->addCustomHooks($module->getCustomHooks());
    $module->registerHook($module->getHookToInstall());

    return true;
}
