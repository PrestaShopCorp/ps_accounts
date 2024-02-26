<?php
function fix_upgrade_7_0_0() {
    $root = __DIR__ . '/..';
    require_once $root . '/src/Hook/Hook.php';
    require_once $root . '/src/Hook/HookableTrait.php';
    require_once $root . '/src/Module/Install.php';
    foreach (glob($root . '/src/Hook/*.php') as $filename) {
        require_once $filename;
    }
}

if (!class_exists('\PrestaShop\Module\PsAccounts\Hook\HookableTrait')) {
    fix_upgrade_7_0_0();
}
