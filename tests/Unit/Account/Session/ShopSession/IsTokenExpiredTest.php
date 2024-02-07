<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\ShopSession;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsTokenExpiredTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var \PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);

        $shopSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertTrue($shopSession->getToken()->isExpired());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('+2 hours'), [
            'sub' => $this->faker->uuid,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var \PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);

        $shopSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertFalse($shopSession->getToken()->isExpired());
    }
}
