<?php
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', '/admin');
}

if (!defined('_PS_MODE_DEV_')) {
    define('_PS_MODE_DEV_', true);
}

$rootDirectory = getenv('_PS_ROOT_DIR_') ?: __DIR__ . '/../../..';
require_once $rootDirectory . '/config/config.inc.php';

//$projectDir = __DIR__ . '/../';
//require_once $projectDir . '/vendor/autoload.php';

//// FIXME: load kernel when necessary
//global $kernel;
//if(!$kernel) {
//    require_once _PS_ROOT_DIR_.'/app/AppKernel.php';
//    $kernel = new \AppKernel('dev', true);
//    $kernel->boot();
//}
