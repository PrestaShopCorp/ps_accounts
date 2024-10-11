<?php
// $module->clearCache();

$filename = __DIR__ . '/../vendor/composer/autoload_real.php';

if (! file_exists($filename)) {
    exit(0);
}

$contents = file_get_contents($filename);
if (preg_match('/(ComposerAutoloaderInit[\w\d]*)/m', $contents, $matches)) {
    $className = $matches[1];

    if (!class_exists($className)) {
        error_log('## ps_accounts autoload : [' . $className . ']' . PHP_EOL);
        require $filename;
        return $className::getLoader();
    }
}
