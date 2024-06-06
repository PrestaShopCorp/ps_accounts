<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\UserTokenRepository;

use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetTokenEmailVerifiedTest extends TestCase
{
    /**
     * @inject
     *
     * @var UserTokenRepository
     */
    protected $repository;

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

        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertTrue($this->repository->getTokenEmailVerified());
    }

//    /**
//     * @test
//     *
//     * @throws \Exception
//     */
//    public function itShouldReturnFalse()
//    {
//        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
//            'sub' => $this->faker->uuid,
//            'email' => $this->faker->safeEmail,
//            'email_verified' => false,
//        ]);
//
//        $refreshToken = null; //$this->makeJwtToken(new \DateTimeImmutable('+1 year'));
//
//        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);
//
//        $this->assertFalse($this->repository->getTokenEmailVerified());
//    }
}
