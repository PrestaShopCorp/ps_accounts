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
     * Local clock 2 minutes behind (ACC-3611): the token expires in 90 s by the local
     * clock but in 90 - 120 < 0 s by the authorization server clock.
     *
     * @test
     */
    public function itShouldBeExpiredWhenLocalClockIsBehind()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('+90 seconds'));

        $this->assertFalse((new Token((string) $jwt, null, 60))->isExpired());
        $this->assertTrue((new Token((string) $jwt, null, 60, 120))->isExpired());
    }

    /**
     * Local clock 2 hours ahead: a fresh 1 hour token looks expired by the local clock,
     * which used to trigger a refresh loop.
     *
     * @test
     */
    public function itShouldNotBeExpiredWhenLocalClockIsAhead()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('-1 hour'));

        $this->assertTrue((new Token((string) $jwt, null, 60))->isExpired());
        $this->assertFalse((new Token((string) $jwt, null, 60, -7200))->isExpired());
    }

    /**
     * @test
     */
    public function itShouldBeExpiredWhenTokenStringIsEmptyEvenWithNegativeClockOffset()
    {
        $this->assertTrue((new Token('', null, 60, -86400))->isExpired());
    }

    /**
     * @test
     */
    public function itShouldMeasureClockOffsetFromIssuedAt()
    {
        $jwt = $this->makeJwtToken(
            new \DateTimeImmutable('+2 hours'),
            [],
            new \DateTimeImmutable('+120 seconds')
        );

        $offset = (new Token((string) $jwt))->measureClockOffset();

        $this->assertGreaterThanOrEqual(119, $offset);
        $this->assertLessThanOrEqual(120, $offset);
    }

    /**
     * @test
     */
    public function itShouldNotMeasureClockOffsetOnInvalidToken()
    {
        $this->assertNull((new Token(''))->measureClockOffset());
        $this->assertNull((new Token('not-a-jwt'))->measureClockOffset());
    }
}
