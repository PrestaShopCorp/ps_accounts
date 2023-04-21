<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsAccountLinkedV4Test extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
        $token = $this->makeFirebaseToken(null, ['email_verified' => true]);
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);
        $shopSession->setToken($token, $refreshToken);

        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);
        $ownerSession->cleanup();

        /** @var ConfigurationRepository $config */
        $config = $this->module->getService(ConfigurationRepository::class);
        $config->updateUserFirebaseIdToken('');
        $config->updateFirebaseEmail($this->faker->safeEmail);

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertTrue($service->isAccountLinkedV4());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $token = $this->makeFirebaseToken(null, ['email_verified' => true]);
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);
        $shopSession->setToken($token, $refreshToken);

        /** @var ConfigurationRepository $config */
        $config = $this->module->getService(ConfigurationRepository::class);
        $config->updateFirebaseEmail('');

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertFalse($service->isAccountLinkedV4());
    }
}
