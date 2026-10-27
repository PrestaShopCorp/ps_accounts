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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Token;

use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class TokenTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldBeExpiredWithoutLeeway()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('-10 seconds'));

        $token = new Token((string) $jwt);

        $this->assertTrue($token->isExpired());
    }

    /**
     * @test
     */
    public function itShouldBeExpiredWhenExpiringWithinLeeway()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('+10 seconds'));

        $token = new Token((string) $jwt, null, 30);

        $this->assertTrue($token->isExpired());
    }

    /**
     * @test
     */
    public function itShouldNotBeExpiredWhenExpiringBeyondLeeway()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('+60 seconds'));

        $token = new Token((string) $jwt, null, 30);

        $this->assertFalse($token->isExpired());
    }

    /**
     * ACC-3675: an expired token must never be reused, whatever the leeway.
     *
     * @test
     */
    public function itShouldBeExpiredAfterExpirationWithLeeway()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('-5 seconds'));

        $token = new Token((string) $jwt, null, 60);

        $this->assertTrue($token->isExpired());
    }

    /**
     * Shop clock 58 minutes ahead of the auth server: a freshly issued 1 hour
     * token is seen as expiring in 2 minutes and must not trigger a refresh loop.
     *
     * @test
     */
    public function itShouldNotBeExpiredWhenFreshTokenIsSeenFromShopClockAhead()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('+2 minutes'));

        $token = new Token((string) $jwt, null, 60);

        $this->assertFalse($token->isExpired());
    }

    /**
     * @test
     */
    public function itShouldNotBeExpiredWhenFreshRegardlessOfLeeway()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('+1 hour'));

        $this->assertFalse((new Token((string) $jwt))->isExpired());
        $this->assertFalse((new Token((string) $jwt, null, 30))->isExpired());
    }

    /**
     * @test
     */
    public function itShouldBeExpiredWhenTokenStringIsEmptyEvenWithLeeway()
    {
        $this->assertTrue((new Token(''))->isExpired());
        $this->assertTrue((new Token('', null, 60))->isExpired());
    }

    /**
     * @test
     */
    public function itShouldBeExpiredWhenTokenStringIsInvalidEvenWithLeeway()
    {
        $this->assertTrue((new Token('not-a-jwt', null, 60))->isExpired());
    }

    /**
     * Instants in the repeated hour of the Europe/Paris DST fall-back
     * (2026-10-25 02:00-03:00 CEST): DateTime::modify('+60 seconds') on a
     * local time used to add 3660 seconds (PHP < 8.1 for the whole hour,
     * PHP >= 8.1 during its last minute).
     *
     * @return array
     */
    public function dstFallBackInstants()
    {
        return [
            'middle of the repeated hour' => ['2026-10-25T00:30:00Z'],
            'last minute of the repeated hour' => ['2026-10-25T00:59:30Z'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider dstFallBackInstants
     *
     * @param string $instant
     */
    public function itShouldApplyLeewayInElapsedSecondsAcrossDstFallBack($instant)
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');

        try {
            $time = strtotime($instant);

            $freshToken = new TokenTestClass(
                (string) $this->makeJwtToken(new \DateTimeImmutable('@' . ($time + 120))),
                null,
                60
            );
            $freshToken->currentTime = $time;
            $this->assertFalse($freshToken->isExpired());

            $expiringToken = new TokenTestClass(
                (string) $this->makeJwtToken(new \DateTimeImmutable('@' . ($time + 30))),
                null,
                60
            );
            $expiringToken->currentTime = $time;
            $this->assertTrue($expiringToken->isExpired());
        } finally {
            date_default_timezone_set($timezone);
        }
    }
}
