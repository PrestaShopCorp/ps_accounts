<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\UserTokenRepository;

use PrestaShop\Module\PsAccounts\Repository\Support\UserTokenRepository;
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
            'user_id' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var UserTokenRepository $tokenRepos */
        $tokenRepos = $this->module->getService(UserTokenRepository::class);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertTrue($tokenRepos->isTokenExpired());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('+2 hours'), [
            'user_id' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var UserTokenRepository $tokenRepos */
        $tokenRepos = $this->module->getService(UserTokenRepository::class);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertFalse($tokenRepos->isTokenExpired());
    }
}
