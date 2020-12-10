<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Provider\CategoryDataProvider;

class ps_AccountsApiCategoriesModuleFrontController extends AbstractApiController
{
    public $type = 'categories';

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
                'apiCategories',
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

        $categoryDataProvider = $this->module->getService(CategoryDataProvider::class);

        $response = $this->handleDataSync($categoryDataProvider);

        $this->exitWithResponse($response);
    }
}
