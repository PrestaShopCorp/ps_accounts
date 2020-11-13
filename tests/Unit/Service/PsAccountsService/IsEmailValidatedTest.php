<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsEmailValidatedTest extends TestCase
{
    /**
     * @not_a_test
     */
    public function it_should_return_true()
    {
        /** @var Configuration $configMock */
        $configMock = $this->getConfigurationMock([]);

        $this->container->singleton(Configuration::class, $configMock);

        $service = new PsAccountsService();

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->container->get(ConfigurationRepository::class);

        $configuration->updateFirebaseEmailIsVerified(1);

        $this->assertTrue($service->isEmailValidated());
    }

    /**
     * @not_a_test
     */
    public function it_should_return_false()
    {
        /** @var Configuration $configMock */
        $configMock = $this->getConfigurationMock([]);

        $this->container->singleton(Configuration::class, $configMock);

        $service = new PsAccountsService();

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->container->get(ConfigurationRepository::class);

        $configuration->updateFirebaseEmailIsVerified(0);

        $this->assertFalse($service->isEmailValidated());
    }
}
