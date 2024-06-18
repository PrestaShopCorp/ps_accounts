<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
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
        $this->linkShop->delete();

        $this->linkShop->setShopUuid($this->faker->uuid);
        $this->linkShop->setOwnerEmail($this->faker->safeEmail);

        $this->assertTrue($this->service->isAccountLinked());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $this->linkShop->delete();

        $this->assertFalse($this->service->isAccountLinked());
    }
}
