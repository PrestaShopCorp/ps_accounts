<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_5_1_0($module)
{
    $module->registerHook($module->getHookToInstall());

    return true;
}
