<?php

namespace PrestaShop\Module\PsAccounts\Cqrs;

use PrestaShop\Module\PsAccounts\Domain\Shop\Query\GetOrRefreshShopToken;
use PrestaShop\Module\PsAccounts\Domain\Shop\QueryHandler\GetOrRefreshShopTokenHandler;
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

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldResolveExistingHandler(): void
    {
        $command = GetOrRefreshShopToken::class;
        $handler = GetOrRefreshShopTokenHandler::class;

        $this->assertEquals($handler, $this->queryBus->resolveHandlerClass($command));
    }
}
