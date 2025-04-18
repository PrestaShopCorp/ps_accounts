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

use PrestaShop\Module\PsAccounts\Account\Command\DeleteUserShopCommand;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

class DeleteUserShopHandler
{
    /**
     * @var AccountsService
     */
    private $accountsService;

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var OwnerSession
     */
    private $ownerSession;

    /**
     * @param AccountsService $accountsService
     * @param ShopContext $shopContext
     * @param ShopSession $shopSession
     * @param OwnerSession $ownerSession
     */
    public function __construct(
        AccountsService $accountsService,
        ShopContext $shopContext,
        ShopSession $shopSession,
        OwnerSession $ownerSession
    ) {
        $this->accountsService = $accountsService;
        $this->shopContext = $shopContext;
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
    }

    /**
     * @param DeleteUserShopCommand $command
     *
     * @return Response
     *
     * @throws RefreshTokenException
     */
    public function handle(DeleteUserShopCommand $command)
    {
        return $this->shopContext->execInShopContext((int) $command->shopId, function () {
            $ownerToken = $this->ownerSession->getValidToken();
            $shopToken = $this->shopSession->getValidToken();

            return $this->accountsService->deleteUserShop(
                $ownerToken->getUuid(),
                $shopToken->getUuid(),
                $ownerToken->getJwt()
            );
        });
    }
}
