<?php

// $module->clearCache();

$autoloadReal = __DIR__ . '/../vendor/composer/autoload_real.php';

if (!file_exists($autoloadReal)) {
    exit(0);
}

$contents = (string) file_get_contents($autoloadReal);
if (preg_match('/(ComposerAutoloaderInit[\w\d]*)/m', $contents, $matches)) {
    $autoloaderClass = $matches[1];

    if (!class_exists($autoloaderClass)) {
        error_log('## ps_accounts autoload : [' . $autoloaderClass . ']' . PHP_EOL);
        require $autoloadReal;

        return $autoloaderClass::getLoader();
    }
}
