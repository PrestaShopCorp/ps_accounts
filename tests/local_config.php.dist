<?php
/**
 * This file allow running tests locally against any PrestaShop sources.
 *
 * Beware running user must have the right permissions on sources :
 *  sudo chown -R www-data:[myuser] $_PS_ROOT_DIR_
 *  sudo chmod -R g+w $_PS_ROOT_DIR_
 *
 * Your PrestaShop database also has to respond locally to the Host and Port configured in the sources :
 * Ex: add local resolution in your /etc/hosts file
 *  127.0.0.1 mariadb
 *
 * TODO: Use a dedicated test Database
 * TODO: Auto fix permissions on sources
 * TODO: Create e2e test endpoint for live e2e tests
 */

if (!getenv('_PS_ROOT_DIR_')) {
    putenv("_PS_ROOT_DIR_=/path/to/my/local/prestashop8");
}

if (!defined('_PS_MODE_DEV_')) {
    define('_PS_MODE_DEV_', true);
}
