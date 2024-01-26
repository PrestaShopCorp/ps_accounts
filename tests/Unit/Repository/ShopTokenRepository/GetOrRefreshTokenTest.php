<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\ShopTokenRepository;

use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetOrRefreshTokenTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
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
     *
     * @throws \Exception
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

        /** @var AnalyticsService $analytics */
        $analytics = $this->module->getService(AnalyticsService::class);

        /** @var ShopTokenRepository $tokenRepos */
        $tokenRepos = $this->getMockBuilder(ShopTokenRepository::class)
            ->setConstructorArgs([$configuration, $analytics])
            ->setMethods(['refreshToken'])
            ->getMock();
        $tokenRepos->method('refreshToken')
            ->willReturn($idTokenRefreshed);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, (string) $tokenRepos->getOrRefreshToken());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function itShouldUpdateRefreshToken()
    {
        $payload = [
            'status' => true,
            'httpCode' => 200,
            'body' => [
                'token' => $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
                    'user_id' => $this->faker->uuid,
                ]),
                'refresh_token' => $this->makeJwtToken(new \DateTimeImmutable('+1 year')),
            ]
        ];

        $client = $this->createMock(AccountsClient::class);
        $client->method('refreshToken')->willReturn($payload);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var AnalyticsService $analytics */
        $analytics = $this->module->getService(AnalyticsService::class);

        /** @var ShopTokenRepository $tokenRepos */
        $tokenRepos = $this->getMockBuilder(ShopTokenRepository::class)
            ->setConstructorArgs([$configuration, $analytics])
            //->disableOriginalConstructor()
            //->disableOriginalClone()
            ->setMethods(['client'])
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $tokenRepos->method('client')
            ->willReturn($client);

        $tokenRepos->updateCredentials(
            (string) $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
                'user_id' => $this->faker->uuid,
                'email' => $this->faker->safeEmail,
            ]),
            (string) $this->makeJwtToken(new \DateTimeImmutable('+1 year'))
        );

        $tokenRepos->getOrRefreshToken();

        $this->assertEquals($payload['body']['token'], $tokenRepos->getToken());
        $this->assertEquals($payload['body']['refresh_token'], $tokenRepos->getRefreshToken());
    }
}
