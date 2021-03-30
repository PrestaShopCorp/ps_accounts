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
            'ssl_activated' => $this->isSslActivated()
        ];
    }

    private function isSslActivated() {
        // TODO It needs to be move to a different class
        // Does a class already exist to get data from a shop?
        $sslQuery = 'SELECT value
                FROM ' . _DB_PREFIX_ . 'configuration
                WHERE name = "PS_SSL_ENABLED_EVERYWHERE"
        ';

        $result = Db::getInstance()->executeS($sslQuery);
        if (isset($result[0]) && isset($result[0]->value))
            return $result[0]->value;

        return 0;
    }
}
