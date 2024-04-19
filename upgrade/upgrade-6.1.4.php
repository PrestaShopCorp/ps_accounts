<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_6_1_4($module)
{
    $module->registerHook($module->getHooksToRegister());

    return true;
}
