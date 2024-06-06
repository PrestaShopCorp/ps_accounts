<?php
//
//namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\UserTokenRepository;
//
//use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
//use PrestaShop\Module\PsAccounts\Tests\TestCase;
//
//class IsTokenExpiredTest extends TestCase
//{
//    /**
//     * @inject
//     *
//     * @var UserTokenRepository
//     */
//    protected $repository;
//
//    /**
//     * @test
//     *
//     * @throws \Exception
//     */
//    public function itShouldReturnTrue()
//    {
//        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
//            'sub' => $this->faker->uuid,
//            'email' => $this->faker->safeEmail,
//        ]);
//
//        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));
//
//        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);
//
//        $this->assertTrue($this->repository->isTokenExpired());
//    }
//
//    /**
//     * @test
//     *
//     * @throws \Exception
//     */
//    public function itShouldReturnFalse()
//    {
//        $idToken = $this->makeJwtToken(new \DateTimeImmutable('+2 hours'), [
//            'sub' => $this->faker->uuid,
//            'email' => $this->faker->safeEmail,
//        ]);
//
//        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));
//
//        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);
//
//        $this->assertFalse($this->repository->isTokenExpired());
//    }
//}
