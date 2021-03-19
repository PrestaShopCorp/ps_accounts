<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Service\ShopTokenService;

class ps_AccountsApiV1ShopTokenModuleFrontController extends AbstractShopRestController
{
    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array
     *
     * @throws Exception
     */
    public function show($shop, array $payload)
    {
        /** @var ShopTokenService $shopTokenService */
        $shopTokenService = $this->module->getService(ShopTokenService::class);

        return [
            'token' => $shopTokenService->getOrRefreshToken(),
            'refresh_token' => $shopTokenService->getRefreshToken(),
        ];
    }
}
