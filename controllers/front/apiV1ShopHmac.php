<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;

class ps_AccountsApiV1ShopHmacModuleFrontController extends AbstractRestController
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
        return [
            'success' => true,
            'message' => 'HMAC stored successfully',
        ];
    }
}
