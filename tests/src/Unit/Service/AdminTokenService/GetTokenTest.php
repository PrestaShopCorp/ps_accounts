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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\AdminTokenService;

use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\Clock\FrozenClock;

class GetTokenTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldExpireOneHourAfterIssuance()
    {
        $service = new AdminTokenServiceTestClass();

        $before = time();
        $token = $service->getToken();
        $after = time();

        $issuedAt = $token->claims()->get('iat')->getTimestamp();
        $expiresAt = $token->claims()->get('exp')->getTimestamp();

        $this->assertGreaterThanOrEqual($before, $issuedAt);
        $this->assertLessThanOrEqual($after, $issuedAt);
        $this->assertSame(3600, $expiresAt - $issuedAt);
        $this->assertTrue($service->verifyToken($token->toString()));
    }

    /**
     * 02:30 local time in both passes of the repeated hour of the Europe/Paris
     * DST fall-back (2026-10-25): DateTimeImmutable::modify('+1 hour') on a
     * local time adds 7200 seconds in the first pass (CEST).
     *
     * @return array
     */
    public function dstFallBackInstants()
    {
        return [
            'first pass of the repeated hour' => ['2026-10-25T00:30:00Z'],
            'second pass of the repeated hour' => ['2026-10-25T01:30:00Z'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider dstFallBackInstants
     *
     * @param string $instant
     */
    public function itShouldExpireOneHourAfterIssuanceAcrossDstFallBack($instant)
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');

        try {
            $now = strtotime($instant);
            $service = new AdminTokenServiceTestClass(new FrozenClock(
                (new \DateTimeImmutable('@' . $now))->setTimezone(new \DateTimeZone('Europe/Paris'))
            ));

            $token = $service->getToken();

            $this->assertSame($now, $token->claims()->get('iat')->getTimestamp());
            $this->assertSame($now + 3600, $token->claims()->get('exp')->getTimestamp());
            $this->assertTrue($service->verifyToken($token->toString()));
        } finally {
            date_default_timezone_set($timezone);
        }
    }
}
