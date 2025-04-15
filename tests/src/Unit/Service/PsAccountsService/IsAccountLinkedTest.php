<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsAccountLinkedTest extends TestCase
{
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
        $this->shopStatus->delete();

        $this->shopStatus->setShopUuid($this->faker->uuid);
        $this->shopStatus->setOwnerEmail($this->faker->safeEmail);

        $this->assertTrue($this->service->isAccountLinked());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $this->shopStatus->delete();

        $this->assertFalse($this->service->isAccountLinked());
    }
}
