<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\ShopTokenRepository;

use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetOrRefreshTokenTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopTokenRepository
     */
    protected $repository;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnValidToken()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idToken, $this->repository->getOrRefreshToken());
    }
}
