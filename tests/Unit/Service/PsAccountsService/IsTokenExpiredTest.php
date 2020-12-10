<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use Lcobucci\JWT\Builder;
use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsTokenExpiredTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_true()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertTrue($service->isTokenExpired());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_return_false()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('+2 hours'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertFalse($service->isTokenExpired());
    }
}
