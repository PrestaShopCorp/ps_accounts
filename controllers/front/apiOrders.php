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
        if (Module::isInstalled('ps_eventbus')) {
            Tools::redirect($this->context->link->getModuleLink(
                'ps_eventbus',
                'apiOrders',
                [
                    'job_id' => Tools::getValue('job_id', ''),
                    'limit' => Tools::getValue('limit'),
                    'full' => Tools::getValue('full'),
                ],
                null,
                null,
                $this->context->shop->id
            ));
        }

        $orderDataProvider = $this->module->getService(OrderDataProvider::class);

        $response = $this->handleDataSync($orderDataProvider);

        $this->exitWithResponse($response);
    }
}
