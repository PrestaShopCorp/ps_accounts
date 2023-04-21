<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsAccountLinkedTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
        /** @var ShopSession $session */
        $session = $this->module->getService(ShopSession::class);
        $session->setToken(
            $this->makeFirebaseToken(null, ['email_verified' => true]),
            base64_encode($this->faker->name)
        );

        /** @var OwnerSession $session */
        $session = $this->module->getService(OwnerSession::class);
        $session->setToken(
            $this->makeFirebaseToken(null, ['email_verified' => true]),
            base64_encode($this->faker->name)
        );

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertTrue($service->isAccountLinked());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        /** @var ShopSession $session */
        $session = $this->module->getService(ShopSession::class);
        $session->setToken(
            $this->makeFirebaseToken(null, ['email_verified' => true]),
            base64_encode($this->faker->name)
        );

        /** @var OwnerSession $session */
        $session = $this->module->getService(OwnerSession::class);
        $session->cleanup();

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertFalse($service->isAccountLinked());
    }
}
