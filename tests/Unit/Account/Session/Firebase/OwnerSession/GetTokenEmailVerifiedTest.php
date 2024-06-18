<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\Firebase\OwnerSession;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetTokenEmailVerifiedTest extends TestCase
{
    /**
     * @inject
     *
     * @var OwnerSession
     */
    protected $session;

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

        $this->session->setToken((string) $idToken, (string) $refreshToken);

        $this->assertTrue($this->session->isEmailVerified());
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

        $this->session->setToken((string) $idToken, (string) $refreshToken);

        $this->assertFalse($this->session->isEmailVerified());
    }
}
