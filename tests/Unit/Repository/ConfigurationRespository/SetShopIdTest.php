<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\ConfigurationRespository;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class SetShopIdTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldPassShopIdCallingGet()
    {
        $shopId = $this->faker->randomNumber();

        $configMock = $this->getMockBuilder(Configuration::class)
            ->setConstructorArgs([\Context::getContext()])
            ->setMethods(['getRaw'])
            ->getMock();

        $configMock->expects($this->once())
            ->method('getRaw')
            ->with(ConfigurationKeys::PS_ACCOUNTS_FIREBASE_EMAIL, null, null, $shopId, false);

        $configuration = new ConfigurationRepository($configMock);
        $configuration->setShopId($shopId);

        $configuration->getFirebaseEmail();
    }

    /**
     * @test
     */
    public function itShouldPassShopIdCallingUpdate()
    {
        $shopId = $this->faker->randomNumber();

        $email = $this->faker->safeEmail;
        $configMock = $this->getMockBuilder(Configuration::class)
            ->setConstructorArgs([\Context::getContext()])
            ->setMethods(['setRaw', 'get'])
            ->getMock();

        $configMock->expects($this->once())
            ->method('get')
            ->with(ConfigurationKeys::PS_PSX_FIREBASE_EMAIL);
        $configMock->expects($this->once())
            ->method('setRaw')
            ->with(ConfigurationKeys::PS_ACCOUNTS_FIREBASE_EMAIL, $email, false, null, $shopId);

        $configuration = new ConfigurationRepository($configMock);
        $configuration->setShopId($shopId);

        $configuration->updateFirebaseEmail($email);
    }
}
