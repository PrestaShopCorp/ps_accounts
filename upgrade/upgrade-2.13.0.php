<?php

function upgrade_module_2_13_0()
{
    $hooksInstalled = true;

    $module = Module::getInstanceByName('ps_accounts');

    $hooks = [
        'actionObjectCartAddAfter',
        'actionObjectCartUpdateAfter',
        'actionObjectOrderAddAfter',
        'actionObjectOrderUpdateAfter',
    ];

    foreach ($hooks as $hook) {
        $hooksInstalled &= $module->registerHook($hook);
    }

    return $hooksInstalled;
}
