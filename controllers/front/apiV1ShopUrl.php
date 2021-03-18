<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;

class ps_AccountsApiV1ShopUrlModuleFrontController extends AbstractShopRestController
{
    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array
     */
    public function show($shop, array $payload)
    {
        return [
            'domain' => $shop->domain,
            'domain_ssl' => $shop->domain_ssl,
        ];
    }
}
