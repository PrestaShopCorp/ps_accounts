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
use PrestaShop\Module\PsAccounts\Vendor\Monolog\Logger as MonologLogger;
use Ps_accounts;

class Logger
{
    /**
     * Detailed debug information
     *
     * @var string
     */
    const DEBUG = 'DEBUG';

    /**
     * Interesting events
     *
     * @var string
     */
    const INFO = 'INFO';

    /**
     * Uncommon events
     *
     * @var string
     */
    const NOTICE = 'NOTICE';

    /**
     * Exceptional occurrences that are not errors
     *
     * @var string
     */
    const WARNING = 'WARNING';

    /**
     * Runtime errors
     *
     * @var string
     */
    const ERROR = 'ERROR';

    /**
     * Critical conditions
     *
     * @var string
     */
    const CRITICAL = 'CRITICAL';

    /**
     * Action must be taken immediately
     *
     * @var string
     */
    const ALERT = 'ALERT';

    /**
     * Urgent alert.
     *
     * @var string
     */
    const EMERGENCY = 'EMERGENCY';

    /**
     * Number of files to rotate
     *
     * @var int
     */
    const MAX_FILES = 15;

    /**
     * @var int
     */
    const DEFAULT_MONOLOG_LEVEL = MonologLogger::ERROR;

    /**
     * @param string $level
     *
     * @return MonologLogger
     */
    public static function create($level = '')
    {
        return (new MonologLogger('ps_accounts'))->pushHandler(
            new RotatingFileHandler(
                self::getPath(),
                static::MAX_FILES,
                self::getMonologLevel($level)
            )
        );
    }

    /**
     * FIXME: misnamed method
     *
     * @return MonologLogger
     */
    public static function getInstance()
    {
        /** @var Ps_accounts $psAccounts */
        $psAccounts = \Module::getInstanceByName('ps_accounts');

        return $psAccounts->getLogger();
    }

    /**
     * @return string
     */
    protected static function getPath()
    {
        $path = _PS_ROOT_DIR_ . '/var/logs/ps_accounts';
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $path = _PS_ROOT_DIR_ . '/log/ps_accounts';
        } elseif (version_compare(_PS_VERSION_, '1.7.4', '<')) {
            $path = _PS_ROOT_DIR_ . '/app/logs/ps_accounts';
        }

        return $path;
    }

    /**
     * @param string $level
     * @param int $default
     *
     * @return int
     */
    protected static function getMonologLevel($level, $default = self::DEFAULT_MONOLOG_LEVEL)
    {
        $logLevel = MonologLogger::toMonologLevel($level);
        /* @phpstan-ignore-next-line */
        return is_int($logLevel) ? $logLevel : $default;
    }
}
