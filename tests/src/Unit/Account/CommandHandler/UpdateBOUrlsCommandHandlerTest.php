<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\UpdateBOUrlCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UpdateBOUrlsCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpdateBOUrlsCommandHandler;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UpdateBOUrlsCommandHandlerTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopContext
     */
    protected $shopContext;

    /**
     * @test
     */
    public function itShouldCallUpdateBOUrlCommandForEachShop()
    {
        $commandBusMock = $this->createMock(CommandBus::class);

        $shopIds = $this->shopContext->isMultishopActive()
            ? $this->shopContext->getMultiShopIds()
            : [null];

        $commandBusMock->expects($this->exactly(count($shopIds)))
            ->method('handle')
            ->with($this->callback(function ($command) {
                return $command instanceof UpdateBOUrlCommand;
            }));

        $handler = new UpdateBOUrlsCommandHandler(
            $this->shopContext,
            $commandBusMock
        );

        $handler->handle(new UpdateBOUrlsCommand());
    }
}

