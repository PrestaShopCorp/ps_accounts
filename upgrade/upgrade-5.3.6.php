<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_5_3_6($module)
{
    $module->addCustomHooks($module->getCustomHooks());
    $module->registerHook($module->getHookToInstall());

    return true;
}
