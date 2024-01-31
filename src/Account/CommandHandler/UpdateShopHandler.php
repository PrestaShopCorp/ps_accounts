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

use PrestaShop\Module\PsAccounts\Account\Command\UpdateShopCommand;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

class UpdateShopHandler
{
    /**
     * @var AccountsClient
     */
    private $accountClient;

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var ShopLinkAccountService
     */
    private $shopLinkAccountService;

    /**
     * @param AccountsClient $accountClient
     * @param ShopContext $shopContext
     * @param ShopLinkAccountService $shopLinkAccountService
     */
    public function __construct(
        AccountsClient $accountClient,
        ShopContext $shopContext,
        ShopLinkAccountService $shopLinkAccountService
    ) {
        $this->accountClient = $accountClient;
        $this->shopContext = $shopContext;
        $this->shopLinkAccountService = $shopLinkAccountService;
    }

    /**
     * @param UpdateShopCommand $command
     *
     * @return array
     *
     * @throws \Exception
     */
    public function handle(UpdateShopCommand $command)
    {
        return $this->shopContext->execInShopContext((int) $command->payload->shopId, function () use ($command) {
            if (!$this->shopLinkAccountService->isAccountLinked()) {
                return null;
            }

            $shopToken = $this->shopLinkAccountService->getShopSession()->getOrRefreshToken();
            $ownerToken = $this->shopLinkAccountService->getOwnerSession()->getOrRefreshToken();

            return $this->accountClient->updateUserShop(
                $ownerToken->getUuid(),
                $shopToken->getUuid(),
                $ownerToken->getJwt(),
                $command->payload
            );
        });
    }
}
