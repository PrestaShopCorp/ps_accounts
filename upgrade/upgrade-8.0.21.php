<?php

/**
 * Removes the legacy "AdminLogin" tab registered for ps_accounts.
 *
 * While this tab exists, the legacy admin Dispatcher (classes/Dispatcher.php)
 * resolves controller=AdminLogin inside modules/ps_accounts/controllers/admin/
 * instead of using the core AdminLoginController, so any core override of
 * AdminLoginController (e.g. 2FA / back-office security modules) is bypassed.
 * This tab is re-created by PrestaShop's module tab auto-registration as long
 * as controllers/admin/AdminLoginController.php ships with the module
 * (see the FIXME in upgrade-7.0.0.php).
 *
 * @param Ps_accounts $module
 *
 * @return bool
 */
function upgrade_module_8_0_21($module)
{
    $tabId = (int) Tab::getIdFromClassName('AdminLogin');
    if ($tabId) {
        $tab = new Tab($tabId);
        if ($tab->module === $module->name) {
            $tab->delete();
        }
    }

    return true;
}
