<?php
require_once __DIR__ . '/bootstrap.php';

if (version_compare(_PS_VERSION_, '1.7', '<')) {
    $module = Module::getInstanceByName('ps_accounts');
    $module->install();
}
