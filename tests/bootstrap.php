<?php
@include (__DIR__ . '/local_config.php');

// depends on BE | FE context
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', '/testing-admin-dir');
}

$rootDirectory = getenv('_PS_ROOT_DIR_') ?: __DIR__ . '/../../..';
$projectDir = __DIR__ . '/../';

require_once $rootDirectory . '/config/config.inc.php';
require_once $projectDir . '/vendor/autoload.php';

global $kernel;
if(!$kernel){
    require_once _PS_ROOT_DIR_.'/app/AppKernel.php';
    $kernel = new \AppKernel('dev', false);
    $kernel->boot();
}
