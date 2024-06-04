<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Domain\Shop\Entity\ShopSession;

use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
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
            'user_id' => $this->faker->uuid,
        ]);
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));
        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'user_id' => $idToken->claims()->get('user_id'),
        ]);

        $response = $this->createApiResponse([
            'token' => $idTokenRefreshed,
            'refresh_token' => $refreshToken,
        ], 200, true);
        $client = $this->createMock(AccountsClient::class);
        $client->method('refreshToken')->willReturn($response);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var ShopSession $shopSession */
        $shopSession = $this->getMockBuilder(ShopSession::class)
            ->setConstructorArgs([
                $client,
                $configuration
            ])
            ->onlyMethods([])
            ->getMock();

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
                'user_id' => $this->faker->uuid,
                'email' => $this->faker->safeEmail,
            ]),
            'refresh_token' => $this->makeJwtToken(new \DateTimeImmutable('+1 year')),
        ];

        $response = $this->createApiResponse($payload, 200, true);
        $client = $this->createMock(AccountsClient::class);
        $client->method('refreshToken')->willReturn($response);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $shopSession = $this->getMockBuilder(ShopSession::class)
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

        $this->assertEquals((string) $payload['token'], (string) $shopSession->getToken()->getJwt());
        $this->assertEquals((string) $payload['refresh_token'], (string) $shopSession->getToken()->getRefreshToken());
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
