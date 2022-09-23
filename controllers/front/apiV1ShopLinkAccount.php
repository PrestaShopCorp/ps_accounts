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
use PrestaShop\Module\PsAccounts\DTO\Api\UpdateShopLinkAccountRequest;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

class ps_AccountsApiV1ShopLinkAccountModuleFrontController extends AbstractShopRestController
{
    /**
     * @var ShopLinkAccountService
     */
    private $shopLinkAccountService;

    /**
     * ps_AccountsApiV1ShopLinkAccountModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);
    }

    /**
     * Expected Payload keys :
     *  - shop_token
     *  - shop_refresh_token
     *  - user_token
     *  - user_refresh_token
     *  - employee_id
     *
     * @param Shop $shop
     * @param array $payload
     *
     * @return array|void
     *
     * @throws Exception
     */
    public function update($shop, array $payload)
    {
        $request = new UpdateShopLinkAccountRequest(array_merge(
            ['employee_id' => ''],
            $payload
        ));

        $this->shopLinkAccountService->updateLinkAccount(
            $request,
            $this->module->getParameter('ps_accounts.verify_account_tokens')
        );

        return [
            'success' => true,
            'message' => 'Link Account stored successfully',
        ];
    }

    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array|void
     */
    public function delete($shop, array $payload)
    {
        $this->shopLinkAccountService->resetLinkAccount();

        return [
            'success' => true,
            'message' => 'Link Account deleted successfully',
        ];
    }
}
