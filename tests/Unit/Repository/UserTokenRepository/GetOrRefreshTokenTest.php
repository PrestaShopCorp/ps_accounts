<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\UserTokenRepository;

use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
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
            'email' => $this->faker->safeEmail,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var UserTokenRepository $tokenRepos */
        $tokenRepos = $this->module->getService(UserTokenRepository::class);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idToken, $tokenRepos->getOrRefreshToken());
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
            'email' => $this->faker->safeEmail,
        ]);

        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var AnalyticsService $analytics */
        $analytics = $this->module->getService(AnalyticsService::class);

        /** @var UserTokenRepository $tokenRepos */
        $tokenRepos = $this->getMockBuilder(UserTokenRepository::class)
            ->setConstructorArgs([$configuration, $analytics])
            ->setMethods(['refreshToken'])
            ->getMock();
        $tokenRepos->method('refreshToken')
            ->willReturn($idTokenRefreshed);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, $tokenRepos->getOrRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldUpdateRefreshToken()
    {
        $payload = [
            'status' => true,
            'httpCode' => 200,
            'body' => [
                'idToken' => $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
                    'user_id' => $this->faker->uuid,
                    'email' => $this->faker->safeEmail,
                ]),
                'refreshToken' => $this->makeJwtToken(new \DateTimeImmutable('+1 year')),
            ],
        ];

        $client = $this->createMock(SsoClient::class);
        $client->method('refreshToken')->willReturn($payload);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var AnalyticsService $analytics */
        $analytics = $this->module->getService(AnalyticsService::class);

        /** @var UserTokenRepository $tokenRepos */
        $tokenRepos = $this->getMockBuilder(UserTokenRepository::class)
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

        $this->assertEquals($payload['body']['idToken'], $tokenRepos->getToken());
        $this->assertEquals($payload['body']['refreshToken'], $tokenRepos->getRefreshToken());
    }
}
