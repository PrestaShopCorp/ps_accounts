<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\UpdateBackOfficeUrlCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UpdateBackOfficeUrlsCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpdateBackOfficeUrlsHandler;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UpdateBackOfficeUrlsHandlerTest extends TestCase
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
    public function itShouldCallUpdateBackOfficeUrlCommandForEachShop()
    {
        $commandBusMock = $this->createMock(CommandBus::class);

        $shopIds = $this->shopContext->isMultishopActive()
            ? $this->shopContext->getMultiShopIds()
            : [null];

        $commandBusMock->expects($this->exactly(count($shopIds)))
            ->method('handle')
            ->with($this->callback(function ($command) {
                return $command instanceof UpdateBackOfficeUrlCommand;
            }));

        $handler = new UpdateBackOfficeUrlsHandler(
            $this->shopContext,
            $commandBusMock
        );

        $handler->handle(new UpdateBackOfficeUrlsCommand());
    }
}

