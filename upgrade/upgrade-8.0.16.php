<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 * @throws Throwable
 */
function upgrade_module_8_0_16($module)
{
    require_once __DIR__ . '/helpers.php';

    migrate_or_create_identities_v8($module, '8.0.16');

    return true;
}
