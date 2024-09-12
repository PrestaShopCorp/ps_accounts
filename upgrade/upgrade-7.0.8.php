<?php
/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 * @throws Throwable
 */
function upgrade_module_7_0_8($module)
{
    // remove mixed-up yaml
    array_map('unlink', [
        $module->getLocalPath() . '/config/config.yml',
        $module->getLocalPath() . '/config/command.yml',
        $module->getLocalPath() . '/config/common.yml',
        $module->getLocalPath() . '/controllers/admin/AdminOAuth2PSAccountsController.php',
        //$module->getLocalPath() . '/config/admin/services.yml',
        //$module->getLocalPath() . '/config/front/services.yml',
    ]);

    return true;
}
