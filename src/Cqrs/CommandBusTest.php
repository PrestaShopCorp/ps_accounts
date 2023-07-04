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

class CommandBusTest extends TestCase
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    public function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->module->getService(CommandBus::class);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldResolveHandler(): void
    {
        $command = 'PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand';
        $handler = 'PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler\ForgetOauth2ClientHandler';

        $this->assertEquals($handler, $this->commandBus->resolveHandlerClass($command));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldResolveExistingHandler(): void
    {
        $command = ForgetOauth2ClientCommand::class;
        $handler = ForgetOauth2ClientHandler::class;

        $this->assertEquals($handler, $this->commandBus->resolveHandlerClass($command));
    }
}
