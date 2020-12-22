<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\ShopTokenService;

use Lcobucci\JWT\Builder;
use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Api\Client\FirebaseClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\ShopTokenService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetOrRefreshTokenTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_valid_token()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        /** @var ShopTokenService $service */
        $service = $this->module->getService(ShopTokenService::class);

        $this->assertEquals((string) $idToken, $service->getOrRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_refresh_expired_token()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'));

        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var FirebaseClient $firebaseClient */
        $firebaseClient = $this->createMock(FirebaseClient::class);

        $firebaseClient->method('exchangeRefreshTokenForIdToken')
            ->willReturn([
                'status' => true,
                'body' => [
                    'id_token' => $idTokenRefreshed,
                    'refresh_token' => $refreshToken,
                ],
            ]);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        $service = new ShopTokenService(
            $firebaseClient,
            $configuration
        );

        $this->assertEquals((string) $idTokenRefreshed, $service->getOrRefreshToken());
    }
}
