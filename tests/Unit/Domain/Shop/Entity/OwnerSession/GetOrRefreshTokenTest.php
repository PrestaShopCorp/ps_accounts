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

        $this->assertEquals((string) $idToken, $ownerSession->getOrRefreshToken()->getToken());
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

        /** @var ConfigurationRepository $configurationRepository */
        $configurationRepository = $this->module->getService(ConfigurationRepository::class);

        /** @var SsoClient $ssoClient */
        $ssoClient = $this->module->getService(SsoClient::class);

        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->getMockBuilder(OwnerSession::class)
            ->setConstructorArgs([$ssoClient, $configurationRepository])
            ->setMethods(['refreshToken'])
            ->getMock();
        $ownerSession->method('refreshToken')
            ->willReturn(new Token($idTokenRefreshed, $refreshToken));

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, $ownerSession->getOrRefreshToken()->getToken());
    }
}
