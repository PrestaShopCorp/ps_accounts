<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Cqrs;

use PrestaShop\Module\PsAccounts\Account\Command\LinkShopCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\LinkShopHandler;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CommandBusTest extends TestCase
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        $this->commandBus = $this->module->getService(CommandBus::class);
    }

    /**
     * @test
     *
     * @throws \Exception
     *
     * @return void
     */
    public function itShouldResolveHandler()
    {
        $command = 'PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand';
        $handler = 'PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler\ForgetOauth2ClientHandler';

        $this->assertEquals($handler, $this->commandBus->resolveHandlerClass($command));
    }

    /**
     * @test
     *
     * @throws \Exception
     *
     * @return void
     */
    public function itShouldResolveExistingHandler()
    {
        $command = LinkShopCommand::class;
        $handler = LinkShopHandler::class;

        $this->assertEquals($handler, $this->commandBus->resolveHandlerClass($command));
    }
}
