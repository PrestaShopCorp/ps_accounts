<?php
echo "starting test... \n";

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', '/admin');
}

if (!defined('_PS_MODE_DEV_')) {
    define('_PS_MODE_DEV_', true);
}

$rootDirectory = __DIR__ . '/../../../../prestashop8';
require_once $rootDirectory . '/config/config.inc.php';

//require_once __DIR__ . '/ps_accounts.php';

include_once __DIR__ . '/../build/ps_accounts/ps_accounts.php';

//use _PhpScoper864b354dfaba\Ps_accounts;
//echo "CLASS " . (new Ps_accounts())->name . "\n";

echo "ending test... \n";
