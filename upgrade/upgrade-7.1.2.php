<?php
/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 * @throws Throwable
 */
function upgrade_module_7_1_2($module)
{
    // FIXME: harmless to let those files untouched
//    array_map('unlink', [
//        $module->getLocalPath() . '/config/config.yml',
//        $module->getLocalPath() . '/config/command.yml',
//    ]);
    return true;
}
