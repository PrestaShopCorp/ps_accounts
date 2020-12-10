<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Provider\ProductDataProvider;

class ps_accountsApiProductsModuleFrontController extends AbstractApiController
{
    public $type = 'products';

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
                'apiProducts',
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

        $productDataProvider = $this->module->getService(ProductDataProvider::class);

        $response = $this->handleDataSync($productDataProvider);

        $this->exitWithResponse($response);
    }
}
