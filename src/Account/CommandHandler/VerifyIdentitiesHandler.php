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

use PrestaShop\Module\PsAccounts\Account\Command\VerifyIdentitiesCommand;
use PrestaShop\Module\PsAccounts\Account\Command\VerifyIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;

class VerifyIdentitiesHandler extends MultiShopHandler
{
    /**
     * @param VerifyIdentitiesCommand $command
     *
     * @return void
     */
    public function handle(VerifyIdentitiesCommand $command)
    {
        $this->handleMulti(function ($multiShopId) use ($command) {
            try {
                $this->commandBus->handle(new VerifyIdentityCommand(
                    $multiShopId,
                    false,
                    $command->origin,
                    $command->source
                ));
            } catch (RefreshTokenException $e) {
                Logger::getInstance()->error($e->getMessage());
            } catch (AccountsException $e) {
                Logger::getInstance()->error($e->getMessage());
            } catch (UnknownStatusException $e) {
                Logger::getInstance()->error($e->getMessage());
            }
        });
    }
}
