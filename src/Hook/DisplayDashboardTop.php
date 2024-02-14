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

namespace PrestaShop\Module\PsAccounts\Hook;

use Exception;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

class DisplayDashboardTop extends Hook
{
    /**
     * @param array $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function execute(array $params = [])
    {
        $shopContext = $this->module->getShopContext();

        /** @var PsAccountsService $accountsService */
        $accountsService = $this->module->getService(PsAccountsService::class);

        if ('AdminShopUrl' === $_GET['controller']) {
            return $this->renderAdminShopUrlWarningIfLinked($shopContext, $accountsService);
        }

        if ('AdminShop' === $_GET['controller']) {
            return $this->renderAdminShopWarningIfLinked($shopContext, $accountsService);
        }
    }

    /**
     * @param ShopContext $shopContext
     * @param PsAccountsService $accountsService
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function renderAdminShopWarningIfLinked($shopContext, $accountsService)
    {
        if (isset($_GET['addshop'])) {
            return;
        }

        if (isset($_GET['updateshop'])) {
            return;
        }

        /** @var ShopProvider $shopProvider */
        $shopProvider = $this->module->getService(ShopProvider::class);

        $shopsTree = $shopProvider->getShopsTree('ps_accounts');
        foreach ($shopsTree as $shopGroup) {
            foreach ($shopGroup['shops'] as $shop) {
                $isLink = $shopContext->execInShopContext($shop['id'], function () use ($accountsService) {
                    return $accountsService->isAccountLinked();
                });
                if ($isLink) {
                    return $this->module->renderDeleteWarningView();
                }
            }
        }
    }

    /**
     * @param ShopContext $shopContext
     * @param PsAccountsService $accountsService
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function renderAdminShopUrlWarningIfLinked($shopContext, $accountsService)
    {
        if (!isset($_GET['updateshop_url'])) {
            return;
        }

        $shopId = $shopContext->getShopIdFromShopUrlId((int) $_GET['id_shop_url']);

        return $shopContext->execInShopContext($shopId, function () use ($accountsService) {
            if ($accountsService->isAccountLinked()) {
                return $this->module->renderUpdateWarningView();
            }
        });
    }
}
