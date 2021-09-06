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
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

class ps_AccountsApiV1ShopHmacModuleFrontController extends AbstractShopRestController
{
    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array|void
     *
     * @throws Exception
     */
    public function update($shop, array $payload)
    {
        /** @var ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

        $shopLinkAccountService->writeHmac(
            $payload['hmac'],
            (string) $shop->id, //$this->context->shop->id,
            _PS_ROOT_DIR_ . '/upload/'
        );

        return [
            'success' => true,
            'message' => 'HMAC stored successfully',
        ];
    }
}
