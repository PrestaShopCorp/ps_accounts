<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_6_1_6($module)
{
    $module->addCustomHooks($module->getCustomHooks());
    $module->registerHook($module->getHooksToRegister());

    return true;
}
