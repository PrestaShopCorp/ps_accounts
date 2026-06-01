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
    public function itShouldNotBeExpiredWithinLeeway()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('-10 seconds'));

        $token = new Token((string) $jwt, null, 30);

        $this->assertFalse($token->isExpired());
    }

    /**
     * @test
     */
    public function itShouldStillBeExpiredBeyondLeeway()
    {
        $jwt = $this->makeJwtToken(new \DateTimeImmutable('-60 seconds'));

        $token = new Token((string) $jwt, null, 30);

        $this->assertTrue($token->isExpired());
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
}
