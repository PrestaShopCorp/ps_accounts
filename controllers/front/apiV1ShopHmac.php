<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

class ps_AccountsApiV1ShopHmacModuleFrontController extends AbstractShopRestController
{
    /**
     * @param array $payload
     *
     * @return array
     *
     * @throws Exception
     */
    public function store(array $payload)
    {
        /** @var ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

        $shopLinkAccountService->writeHmac(
            $payload['hmac'],
            $this->context->shop->id,
            _PS_ROOT_DIR_ . '/upload/'
        );

        return [
            'success' => true,
            'message' => 'HMAC stored successfully',
        ];
    }
}
