<?php

namespace PrestaShop\Module\PsAccounts\Cqrs;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler\ForgetOauth2ClientHandler;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\LinkShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\CommandHandler\LinkShopCommandHandler;
use PrestaShop\Module\PsAccounts\Domain\Shop\Dto\LinkShop;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class QueryBusTest extends TestCase
{
    /**
     * @var QueryBus
     */
    private $queryBus;

    public function setUp(): void
    {
        parent::setUp();

        $this->queryBus = $this->module->getService(QueryBus::class);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldResolveHandler(): void
    {
        $command = 'PrestaShop\Module\PsAccounts\Domain\Account\Query\FooBar';
        $handler = 'PrestaShop\Module\PsAccounts\Domain\Account\QueryHandler\FooBarHandler';

        $this->assertEquals($handler, $this->queryBus->resolveHandlerClass($command));
    }
}
