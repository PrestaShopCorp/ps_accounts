<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\Firebase\OwnerSession;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsTokenExpiredTest extends TestCase
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
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $this->session->setToken((string) $idToken, (string) $refreshToken);

        $this->assertTrue($this->session->getToken()->isExpired());
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
            'email' => $this->faker->safeEmail,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $this->session->setToken((string) $idToken, (string) $refreshToken);

        $this->assertFalse($this->session->getToken()->isExpired());
    }
}
