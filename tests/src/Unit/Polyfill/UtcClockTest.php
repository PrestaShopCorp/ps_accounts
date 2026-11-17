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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Polyfill;

use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Polyfill\UtcClock;
use PrestaShop\Module\PsAccounts\Service\AdminTokenService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\Clock\FrozenClock;

class UtcClockTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldGiveTheCurrentTimeInUtcWithMicroseconds()
    {
        $before = microtime(true);
        $now = UtcClock::getInstance()->now();
        $after = microtime(true);

        $this->assertSame('+00:00', $now->format('P'));
        $this->assertGreaterThanOrEqual(floor($before * 1e6), (float) $now->format('Uu'));
        $this->assertLessThanOrEqual(ceil($after * 1e6), (float) $now->format('Uu'));
    }

    /**
     * @test
     */
    public function itShouldShareOneDefaultInstance()
    {
        $this->assertSame(UtcClock::getInstance(), UtcClock::orDefault(null));
    }

    /**
     * @test
     */
    public function itShouldKeepAGivenClock()
    {
        $clock = new FrozenClock(new \DateTimeImmutable('@0'));

        $this->assertSame($clock, UtcClock::orDefault($clock));
    }

    /**
     * @test
     */
    public function itShouldRejectAnythingElseThanAClock()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Token('', null, 0, 0, new \DateTimeImmutable());
    }

    /**
     * @test
     */
    public function itShouldRejectAnythingElseThanAClockInAdminTokenService()
    {
        $this->expectException(\InvalidArgumentException::class);

        new AdminTokenService('now');
    }
}
