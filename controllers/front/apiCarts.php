<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Provider\CartDataProvider;

class ps_AccountsApiCartsModuleFrontController extends AbstractApiController
{
    public $type = 'carts';

    /**
     * @throws PrestaShopException
     *
     * @return void
     */
    public function postProcess()
    {
        $cartDataProvider = $this->module->getService(CartDataProvider::class);

        $response = $this->handleDataSync($cartDataProvider);

        $this->exitWithResponse($response);
    }
}
