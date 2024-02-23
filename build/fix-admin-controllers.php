<?php
/**
 * This helper is needed to "trick" composer autoloader to load the prefixed files
 * Otherwise if owncloud/core contains the same libraries ( i.e. guzzle ) it won't
 * load the files, as the file hash is the same and thus composer would think this was already loaded
 *
 * More information also found here: https://github.com/humbug/php-scoper/issues/298
 */
$controllers_path =  __DIR__ . '/ps_accounts/controllers/admin';
echo "Fixing $controllers_path \n";
foreach (glob($controllers_path . '/*Controller.*') as $filename) {
    $contents = file_get_contents($filename);
    // strips namespace
    $contents = preg_replace('/namespace .*?;/', '', $contents);
    // strips alias
    $contents = preg_replace('/\\\\class_alias\(.*?;/', '', $contents);
    file_put_contents($filename, $contents);
}

$filename = __DIR__ . '/ps_accounts/src/Hook/ActionAdminControllerInitBefore.php';
$contents = file_get_contents($filename);
$contents = preg_replace('/use .*?(AdminLoginPsAccountsController)/', 'use $1', $contents);
file_put_contents($filename, $contents);
