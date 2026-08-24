<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 * @throws Throwable
 */
function upgrade_module_8_0_17($module)
{
    require_once __DIR__ . '/helpers.php';

    migrate_or_create_identities_v8($module, '8.0.17');

    return true;
}
