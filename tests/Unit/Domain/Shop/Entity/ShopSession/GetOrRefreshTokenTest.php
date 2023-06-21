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

        /** @var ShopSession $shopSession */
        $shopSession = $this->getMockBuilder(ShopSession::class)
            ->setConstructorArgs([
                $this->module->getService(AccountsClient::class),
                $configuration
            ])
            ->setMethods(['refreshToken'])
            ->getMock();
        $shopSession->method('refreshToken')
            ->willReturn(new Token($idTokenRefreshed, $refreshToken));

        $shopSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, (string) $shopSession->getOrRefreshToken()->getJwt());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function itShouldUpdateRefreshToken()
    {
        $payload = [
            'token' => $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
                'user_id' => $this->faker->uuid,
            ]),
            'refresh_token' => $this->makeJwtToken(new \DateTimeImmutable('+1 year')),
        ];

        $client = $this->createMock(AccountsClient::class);
        $client->method('refreshToken')->willReturn($payload);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var ShopTokenRepository $tokenRepos */
        $tokenRepos = $this->getMockBuilder(ShopTokenRepository::class)
            ->setConstructorArgs([$configuration])
            //->disableOriginalConstructor()
            //->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $tokenRepos->method('client')
            ->willReturn($client);

        $tokenRepos->updateCredentials(
            $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
                'user_id' => $this->faker->uuid,
                'email' => $this->faker->safeEmail,
            ]),
            $this->makeJwtToken(new \DateTimeImmutable('+1 year'))
        );

        $tokenRepos->getOrRefreshToken();

        $this->assertEquals($payload['token'], $tokenRepos->getToken());
        $this->assertEquals($payload['refresh_token'], $tokenRepos->getRefreshToken());
    }
}
