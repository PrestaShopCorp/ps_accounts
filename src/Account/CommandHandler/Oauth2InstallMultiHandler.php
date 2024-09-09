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

namespace PrestaShop\Module\PsAccounts\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\Oauth2InstallCommand;
use PrestaShop\Module\PsAccounts\Account\Command\Oauth2InstallMultiCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\AbstractClass\MultiShopHandlerAbstract;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class Oauth2InstallMultiHandler extends MultiShopHandlerAbstract
{
    /**
     * @var ConfigurationRepository
     */
    private $configRepo;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @param CommandBus $commandBus
     * @param ConfigurationRepository $configRepo
     */
    public function __construct(
        CommandBus $commandBus,
        ConfigurationRepository $configRepo
    ) {
        $this->commandBus = $commandBus;
        $this->configRepo = $configRepo;
    }

    /**
     * @param Oauth2InstallMultiCommand $command
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function handle(Oauth2InstallMultiCommand $command)
    {
        foreach ($this->getShops($this->configRepo->isMultishopActive()) as $id) {
            $this->commandBus->handle(new Oauth2InstallCommand($id, []));
        }
    }
}
