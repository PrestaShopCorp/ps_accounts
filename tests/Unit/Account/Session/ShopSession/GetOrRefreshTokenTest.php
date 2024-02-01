<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\ShopSession;

use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
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
            'sub' => $this->faker->uuid,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);

        $shopSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idToken, $shopSession->getOrRefreshToken()->getJwt());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldRefreshExpiredToken()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
        ]);
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));
        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $idToken->claims()->get('sub'),
        ]);

        $response = $this->createApiResponse([
            'token' => $idTokenRefreshed,
            'refresh_token' => $refreshToken,
        ], 200, true);
        $client = $this->createMock(AccountsClient::class);
        $client->method('refreshToken')->willReturn($response);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var AnalyticsService $analyticsService */
        $analyticsService = $this->createMock(AnalyticsService::class);

        $shopSession = new ShopSession($client, $configuration, $analyticsService);

        $shopSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, (string) $shopSession->getOrRefreshToken()->getJwt());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldStoreNewRefreshToken()
    {
        $payload = [
            'token' => $this->makeJwtToken(new \DateTimeImmutable('+1 hour'), [
                'sub' => $this->faker->uuid,
                'email' => $this->faker->safeEmail,
            ]),
            'refresh_token' => $this->makeJwtToken(new \DateTimeImmutable('+1 year')),
        ];

        $response = $this->createApiResponse($payload, 200, true);
        $client = $this->createMock(AccountsClient::class);
        $client->method('refreshToken')->willReturn($response);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var AnalyticsService $analyticsService */
        $analyticsService = $this->createMock(AnalyticsService::class);

        $shopSession = new ShopSession($client, $configuration, $analyticsService);

        $shopSession->setToken(
            (string) $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
                'sub' => $this->faker->uuid,
                'email' => $this->faker->safeEmail,
            ]),
            (string) $this->makeJwtToken(new \DateTimeImmutable('yesterday'))
        );

        $shopSession->getOrRefreshToken();

        $this->assertEquals((string) $payload['token'], (string) $shopSession->getToken()->getJwt());
        $this->assertEquals((string) $payload['refresh_token'], (string) $shopSession->getToken()->getRefreshToken());
    }

    /**
     * @param array $body
     * @param int $httpCode
     * @param bool $status
     *
     * @return array
     */
    protected function createApiResponse(array $body, $httpCode, $status)
    {
        return [
            'status' => $status,
            'httpCode' => $httpCode,
            'body' => $body,
        ];
    }
}
