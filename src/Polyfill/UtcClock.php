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

namespace PrestaShop\Module\PsAccounts\Polyfill;

use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\Clock\Clock;

/**
 * System clock in UTC with microseconds, also on PHP < 7.1 where
 * new \DateTimeImmutable('now') has none.
 */
class UtcClock implements Clock
{
    /**
     * @var UtcClock|null
     */
    private static $instance;

    /**
     * @return UtcClock
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param mixed $clock
     *
     * @return Clock the given clock, or the shared UTC clock when null
     *
     * @throws \InvalidArgumentException
     */
    public static function orDefault($clock)
    {
        if (null === $clock) {
            return self::getInstance();
        }

        if (!$clock instanceof Clock) {
            throw new \InvalidArgumentException('Expected an instance of ' . Clock::class);
        }

        return $clock;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function now()
    {
        // explicit UTC: PHP 5.6 warns when date.timezone is not set
        $utc = new \DateTimeZone('UTC');
        $now = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', microtime(true)), $utc);

        return $now ?: new \DateTimeImmutable('@' . time(), $utc);
    }
}
