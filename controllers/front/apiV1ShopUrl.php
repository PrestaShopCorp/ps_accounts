<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;

class ps_AccountsApiV1ShopUrlModuleFrontController extends AbstractShopRestController
{
    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function show($id, array $payload)
    {
        $shopUrl = new ShopUrl($id);

        return [
            'domain' => $shopUrl->domain,
            'domain_ssl' => $shopUrl->domain_ssl,
        ];
    }
}
