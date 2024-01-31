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

use Hook;
use PrestaShop\Module\PsAccounts\Account\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Hook\ActionShopAccountUnlinkAfter;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

class UnlinkShopHandler
{
    /**
     * @var ShopLinkAccountService
     */
    private $shopLinkAccountService;

    /**
     * @param ShopLinkAccountService $shopLinkAccountService
     */
    public function __construct(ShopLinkAccountService $shopLinkAccountService)
    {
        $this->shopLinkAccountService = $shopLinkAccountService;
    }

    /**
     * @param UnlinkShopCommand $command
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function handle(UnlinkShopCommand $command)
    {
        // FIXME: exec in shop context with $command->shopId

        $hookData = [
            'shopUuid' => $this->shopLinkAccountService->getShopSession()->getToken()->getUuid(),
            'shopId' => $command->shopId,
        ];

        $this->shopLinkAccountService->resetLinkAccount();

        Hook::exec(ActionShopAccountUnlinkAfter::getName(), $hookData);
    }
}
