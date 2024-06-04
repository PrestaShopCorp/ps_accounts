<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

<<<<<<< HEAD
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
=======
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsAccountLinkedTest extends TestCase
{
    /**
     * @inject
     *
     * @var LinkShop
     */
    protected $linkShop;

    /**
     * @inject
     *
     * @var PsAccountsService
     */
    protected $service;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
<<<<<<< HEAD
        $this->linkShop->delete();

        $this->linkShop->setShopUuid($this->faker->uuid);
        $this->linkShop->setOwnerEmail($this->faker->safeEmail);
=======
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
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        $this->assertTrue($this->service->isAccountLinked());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
<<<<<<< HEAD
        $this->linkShop->delete();

        $this->assertFalse($this->service->isAccountLinked());
=======
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
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }
}
