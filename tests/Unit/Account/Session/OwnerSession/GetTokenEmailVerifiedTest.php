<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\OwnerSession;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetTokenEmailVerifiedTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
            'email_verified' => true,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertTrue($ownerSession->isEmailVerified());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
            'email_verified' => false,
        ]);

        $refreshToken = null; //$this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertFalse($ownerSession->isEmailVerified());
    }
}