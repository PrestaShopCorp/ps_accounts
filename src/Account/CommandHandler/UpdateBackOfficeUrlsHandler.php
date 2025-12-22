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

use Exception;
use PrestaShop\Module\PsAccounts\Account\Command\UpdateBackOfficeUrlCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UpdateBackOfficeUrlsCommand;
use PrestaShop\Module\PsAccounts\Log\Logger;
use Throwable;

class UpdateBackOfficeUrlsHandler extends MultiShopHandler
{
    /**
     * @param UpdateBackOfficeUrlsCommand $command
     *
     * @return void
     */
    public function handle(UpdateBackOfficeUrlsCommand $command)
    {
        $this->handleMulti(function ($multiShopId) {
            try {
                $updateBackOfficeUrlCommand = new UpdateBackOfficeUrlCommand($multiShopId);
                $this->commandBus->handle($updateBackOfficeUrlCommand);
            } catch (Exception $e) {
                Logger::getInstance()->error($e->getMessage());
            } catch (Throwable $e) {
                Logger::getInstance()->error($e->getMessage());
            }
        });
    }
}
