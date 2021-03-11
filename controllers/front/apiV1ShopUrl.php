<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;

class ps_AccountsApiV1ShopUrlModuleFrontController extends AbstractRestController
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
        $shopUrl = new ShopUrl($payload['shop_id']);

        return [
            'domain' => $shopUrl->domain,
            'domain_ssl' => $shopUrl->domain_ssl,
        ];
    }
}
