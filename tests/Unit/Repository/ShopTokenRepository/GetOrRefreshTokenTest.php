<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\ShopTokenRepository;

use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetOrRefreshTokenTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldReturnValidToken()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'user_id' => $this->faker->uuid,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ShopTokenRepository $service */
        $service = $this->module->getService(ShopTokenRepository::class);

        $service->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idToken, $service->getOrRefreshToken());
    }

    /**
     * @test
     */
    public function itShouldRefreshExpiredToken()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'user_id' => $this->faker->uuid,
        ]);

        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'user_id' => $idToken->claims()->get('user_id'),
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var ShopTokenRepository $tokenRepos */
        $tokenRepos = $this->getMockBuilder(ShopTokenRepository::class)
            ->setConstructorArgs([$configuration])
            ->setMethods(['refreshToken'])
            ->getMock();
        $tokenRepos->method('refreshToken')
            ->willReturn($idTokenRefreshed);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, (string) $tokenRepos->getOrRefreshToken());
    }
}
