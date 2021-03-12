<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Service\ShopTokenService;

class ps_AccountsApiV1ShopTokenModuleFrontController extends AbstractShopRestController
{
    /**
     * @param array $payload
     *
     * @return array
     *
     * @throws Exception
     */
    public function index(array $payload)
    {
        /** @var ShopTokenService $shopTokenService */
        $shopTokenService = $this->module->getService(ShopTokenService::class);

        return [
            'token' => $shopTokenService->getOrRefreshToken(),
            'refreshToken' => $shopTokenService->getRefreshToken(),
        ];
    }
}
