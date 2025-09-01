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

use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentitiesCommand;
use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

class CreateIdentitiesHandler extends MultiShopHandler
{
    /**
     * @param CreateIdentitiesCommand $command
     *
     * @return void
     */
    public function handle(CreateIdentitiesCommand $command)
    {
        $this->handleMulti(function ($multiShopId) use ($command) {
            try {
                $this->commandBus->handle(new CreateIdentityCommand(
                    $multiShopId,
                    false,
                    AccountsService::ORIGIN_INSTALL,
                    $command->source
                ));
            } catch (RefreshTokenException $e) {
                Logger::getInstance()->error($e->getMessage());
            } catch (AccountsException $e) {
                Logger::getInstance()->error($e->getMessage());
            }
        });
    }
}
