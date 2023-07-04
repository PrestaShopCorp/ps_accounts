<?php

namespace PrestaShop\Module\PsAccounts\Cqrs;

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
