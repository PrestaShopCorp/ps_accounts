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
    if ($module->getOverrides() != null) {
        try {
            $module->installOverrides();
        } catch (Exception $e) {
            $module->getLogger()->error(Context::getContext()->getTranslator()->trans(
                'Unable to install override: %s', [$e->getMessage()], 'Admin.Modules.Notification')
            );
            $module->uninstallOverrides();

            return false;
        }
    }

    return true;
}
