<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Provider\OrderDataProvider;

class ps_AccountsApiOrdersModuleFrontController extends AbstractApiController
{
    public $type = 'orders';

    /**
     * @throws PrestaShopException
     *
     * @return void
     */
    public function postProcess()
    {
        $orderDataProvider = $this->module->getService(OrderDataProvider::class);

        $response = $this->handleDataSync($orderDataProvider);

        $this->exitWithResponse($response);
    }
}
