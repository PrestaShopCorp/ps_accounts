<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Domain\Shop\Entity\OwnerSession;

use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Token;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
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

        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idToken, $ownerSession->getOrRefreshToken()->getJwt());
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
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));
        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $payload = [
            'idToken' => $idTokenRefreshed,
            'refreshToken' => $refreshToken,
        ];

        $response = $this->createApiResponse($payload, 200, true);
        $client = $this->createMock(SsoClient::class);
        $client->method('refreshToken')->willReturn($response);

        /** @var ConfigurationRepository $configurationRepository */
        $configurationRepository = $this->module->getService(ConfigurationRepository::class);

        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->getMockBuilder(OwnerSession::class)
            ->setConstructorArgs([$client, $configurationRepository])
            ->onlyMethods([])
            ->getMock();

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

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
                'user_id' => $this->faker->uuid,
                'email' => $this->faker->safeEmail,
            ]),
            'refreshToken' => $this->makeJwtToken(new \DateTimeImmutable('+1 year')),
        ];

        $response = $this->createApiResponse($payload, 200, true);
        $client = $this->createMock(SsoClient::class);
        $client->method('refreshToken')->willReturn($response);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $shopSession = $this->getMockBuilder(OwnerSession::class)
            ->setConstructorArgs([
                $client,
                $configuration
            ])
            ->onlyMethods([])
            ->getMock();

        $shopSession->setToken(
            $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
                'user_id' => $this->faker->uuid,
                'email' => $this->faker->safeEmail,
            ]),
            $this->makeJwtToken(new \DateTimeImmutable('yesterday'))
        );

        $shopSession->getOrRefreshToken();

        $this->assertEquals((string) $payload['idToken'], (string) $shopSession->getToken()->getJwt());
        $this->assertEquals((string) $payload['refreshToken'], (string) $shopSession->getToken()->getRefreshToken());
    }

    protected function createApiResponse(array $body, int $httpCode, bool $status): array
    {
        return [
            'status' => $status,
            'httpCode' => $httpCode,
            'body' => $body,
        ];
    }
}
