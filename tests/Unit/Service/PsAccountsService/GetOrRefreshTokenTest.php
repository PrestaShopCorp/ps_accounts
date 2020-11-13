<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use Lcobucci\JWT\Builder;
use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Api\Client\FirebaseClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
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
        //$date = (new \DateTime('tomorrow'));
        $date = $this->faker->dateTimeBetween('now', '+2 hours');

        $idToken = (new Builder())
            ->expiresAt($date->getTimestamp())
            //->withClaim('uid', $this->faker->uuid)
            ->getToken();

        $refreshToken = (new Builder())->getToken();

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

//        /** @var Configuration $configMock */
//        $configMock = $this->getConfigurationMock([
//            [Configuration::PS_PSX_FIREBASE_REFRESH_DATE, false, $date->format('Y-m-d h:m:s')],
//        ]);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertEquals((string) $idToken, $service->getOrRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_refresh_expired_token()
    {
        /* FIXME */ $this->markTestSkipped('Howto inject mocked FirebaseClient into container ?');

        $date = $this->faker->dateTime('now');

        $idToken = (new Builder())
            ->expiresAt($date->getTimestamp())
            //->withClaim('uid', $this->faker->uuid)
            ->getToken();

        $idTokenRefreshed = (new Builder())
            ->expiresAt($this->faker->dateTimeBetween('+2 hours', '+4 hours')->getTimestamp())
            //->withClaim('uid', $this->faker->uuid)
            ->getToken();

        $refreshToken = (new Builder())->getToken();

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

//        /** @var Configuration $configMock */
//        $configMock = $this->getConfigurationMock([
//            [Configuration::PS_PSX_FIREBASE_REFRESH_DATE, false, $date->format('Y-m-d h:m:s')],
//        ]);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertEquals((string) $idTokenRefreshed, $service->getOrRefreshToken());
    }
}
