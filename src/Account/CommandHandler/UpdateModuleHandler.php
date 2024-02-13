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

use PrestaShop\Module\PsAccounts\Account\Command\UpdateModuleCommand;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;

class UpdateModuleHandler
{
    /**
     * @var LinkShop
     */
    private $linkShop;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var AccountsClient
     */
    private $accountsClient;

    public function __construct(
        AccountsClient $accountsClient,
        LinkShop $linkShop,
        ShopSession $shopSession
    ) {
        $this->accountsClient = $accountsClient;
        $this->linkShop = $linkShop;
        $this->shopSession = $shopSession;
    }

    /**
     * @param UpdateModuleCommand $command
     *
     * @return void
     */
    public function handle(UpdateModuleCommand $command)
    {
        $this->accountsClient->updateShopModule(
            $this->linkShop->getShopUuid(),
            (string) $this->shopSession->getToken(),
            $command->payload
        );
    }
}
