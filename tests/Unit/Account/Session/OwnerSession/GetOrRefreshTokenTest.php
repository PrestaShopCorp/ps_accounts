<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\OwnerSession;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetOrRefreshTokenTest extends TestCase
{
    /**
     * @inject
     * @var OwnerSession
     */
    protected $ownerSession;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnValidToken()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $this->ownerSession->setToken((string) $idToken);

        $this->assertEquals((string) $idToken, (string) $this->ownerSession->getOrRefreshToken());
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
            'email' => $this->faker->safeEmail,
        ]);
        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $payload = [
            'userToken' => $idTokenRefreshed,
            'shopToken' => $idTokenRefreshed,
        ];

        $response = $this->createApiResponse($payload, 200, true);
        $client = $this->createMock(AccountsClient::class);
        $client->method('firebaseTokens')->willReturn($response);

        /** @var ConfigurationRepository $configurationRepository */
        $configurationRepository = $this->module->getService(ConfigurationRepository::class);

        /** @var AnalyticsService $analyticsService */
        $analyticsService = $this->createMock(AnalyticsService::class);

        $ownerSession = new OwnerSession($client, $configurationRepository, $analyticsService);
        $ownerSession->setToken((string) $idToken);

        $this->assertEquals((string) $idTokenRefreshed, $ownerSession->getOrRefreshToken()->getJwt());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldStoreNewRefreshToken()
    {
        $payload = [
            'idToken' => $this->makeJwtToken(new \DateTimeImmutable('+1 hour'), [
                'sub' => $this->faker->uuid,
                'email' => $this->faker->safeEmail,
            ]),
            'refreshToken' => $this->makeJwtToken(new \DateTimeImmutable('+1 year')),
        ];

        $response = $this->createApiResponse($payload, 200, true);
        $client = $this->createMock(SsoClient::class);
        $client->method('refreshToken')->willReturn($response);

        /** @var ConfigurationRepository $configurationRepository */
        $configurationRepository = $this->module->getService(ConfigurationRepository::class);

        /** @var AnalyticsService $analyticsService */
        $analyticsService = $this->createMock(AnalyticsService::class);

        $ownerSession = new OwnerSession($client, $configurationRepository, $analyticsService);

        $ownerSession->setToken(
            (string) $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
                'sub' => $this->faker->uuid,
                'email' => $this->faker->safeEmail,
            ]),
            (string) $this->makeJwtToken(new \DateTimeImmutable('tomorrow'))
        );

        $ownerSession->getOrRefreshToken();

        $this->assertEquals((string) $payload['idToken'], (string) $ownerSession->getToken()->getJwt());
        $this->assertEquals((string) $payload['refreshToken'], (string) $ownerSession->getToken()->getRefreshToken());
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
