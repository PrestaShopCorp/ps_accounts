<?php

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
