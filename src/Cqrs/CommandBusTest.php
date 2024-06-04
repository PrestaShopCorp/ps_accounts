<?php
<<<<<<< HEAD
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
=======

namespace PrestaShop\Module\PsAccounts\Cqrs;

use PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler\ForgetOauth2ClientHandler;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CommandBusTest extends TestCase
{
    /**
     * @var CommandBus
     */
    private $commandBus;

<<<<<<< HEAD
    /**
     * @return void
     *
     * @throws \Exception
     */
    public function setUp()
=======
    public function setUp(): void
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    {
        parent::setUp();

        $this->commandBus = $this->module->getService(CommandBus::class);
    }

    /**
     * @test
     *
     * @throws \Exception
<<<<<<< HEAD
     *
     * @return void
     */
    public function itShouldResolveHandler()
=======
     */
    public function itShouldResolveHandler(): void
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    {
        $command = 'PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand';
        $handler = 'PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler\ForgetOauth2ClientHandler';

        $this->assertEquals($handler, $this->commandBus->resolveHandlerClass($command));
    }

    /**
     * @test
     *
     * @throws \Exception
<<<<<<< HEAD
     *
     * @return void
     */
    public function itShouldResolveExistingHandler()
    {
        $command = LinkShopCommand::class;
        $handler = LinkShopHandler::class;
=======
     */
    public function itShouldResolveExistingHandler(): void
    {
        $command = ForgetOauth2ClientCommand::class;
        $handler = ForgetOauth2ClientHandler::class;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        $this->assertEquals($handler, $this->commandBus->resolveHandlerClass($command));
    }
}
