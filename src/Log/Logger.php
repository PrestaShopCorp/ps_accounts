<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Log;

use PrestaShop\Module\PsAccounts\Vendor\Monolog\Handler\RotatingFileHandler;
use PrestaShop\Module\PsAccounts\Vendor\Monolog\Logger as MonoLogger;
use Ps_accounts;

class Logger
{
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const NOTICE = 'NOTICE';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    const ALERT = 'ALERT';
    const EMERGENCY = 'EMERGENCY';
    const MAX_FILES = 15;

    /**
     * @param string|null $logLevel
     *
     * @return MonoLogger
     */
    public static function create($logLevel = null)
    {
        $logLevel = self::getLevel($logLevel);
        $monologLevel = MonoLogger::toMonologLevel($logLevel);
        if (!is_int($monologLevel)) {
            $monologLevel = MonoLogger::DEBUG;
        }

        $path = _PS_ROOT_DIR_ . '/var/logs/ps_accounts';

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $path = _PS_ROOT_DIR_ . '/log/ps_accounts';
        } elseif (version_compare(_PS_VERSION_, '1.7.4', '<')) {
            $path = _PS_ROOT_DIR_ . '/app/logs/ps_accounts';
        }

        $rotatingFileHandler = new RotatingFileHandler($path, static::MAX_FILES, $monologLevel);
        $logger = new MonoLogger('ps_accounts');
        $logger->pushHandler($rotatingFileHandler);

        return $logger;
    }

    /**
     * @return Monologger
     */
    public static function getInstance()
    {
        /** @var Ps_accounts $psAccounts */
        $psAccounts = \Module::getInstanceByName('ps_accounts');

        return $psAccounts->getLogger();
    }

    /**
     * @param string|null $logLevel
     * @param string $parameter
     *
     * @return mixed
     */
    public static function getLevel($logLevel, $parameter = 'ps_accounts.log_level')
    {
        if ($logLevel === null) {
            /** @var Ps_accounts $psAccounts */
            $psAccounts = \Module::getInstanceByName('ps_accounts');
            if ($psAccounts->hasParameter($parameter)) {
                $logLevel = $psAccounts->getParameter($parameter);
            }
        }

        return $logLevel;
    }
}
