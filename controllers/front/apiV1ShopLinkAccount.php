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

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\LinkShop;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\UnlinkShop;
use PrestaShop\Module\PsAccounts\Dto\Api\UpdateShopLinkAccountRequest;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

class ps_AccountsApiV1ShopLinkAccountModuleFrontController extends AbstractShopRestController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * ps_AccountsApiV1ShopLinkAccountModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->commandBus = $this->module->getService(CommandBus::class);

        $this->psAccountsService = $this->module->getService(PsAccountsService::class);
    }

    /**
     * @throws RefreshTokenException
     * @throws Exception
     */
    public function update(Shop $shop, UpdateShopLinkAccountRequest $request): array
    {
        $this->commandBus->execute(new LinkShop(
            $request,
            $this->module->getParameter('ps_accounts.verify_account_tokens')
        ));

        Hook::exec(Ps_accounts::HOOK_ACTION_SHOP_ACCOUNT_LINK_AFTER, [
            'shopUuid' => $this->psAccountsService->getShopUuid(),
            'shopId' => $shop->id,
        ]);

        return [
            'success' => true,
            'message' => 'Link Account stored successfully',
        ];
    }

    /**
     * @throws Exception
     */
    public function delete(Shop $shop, array $payload): array
    {
        $hookData = [
            'shopUuid' => $this->psAccountsService->getShopUuid(),
            'shopId' => $shop->id,
        ];

        $this->commandBus->execute(new UnlinkShop($shop->id));

        Hook::exec(Ps_accounts::HOOK_ACTION_SHOP_ACCOUNT_UNLINK_AFTER, $hookData);

        return [
            'success' => true,
            'message' => 'Link Account deleted successfully',
        ];
    }
}
