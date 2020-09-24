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
        $productDataProvider = $this->module->getService(CategoryDataProvider::class);

        $response = $this->handleDataSync($productDataProvider);

        $this->ajaxDie($response);
    }
}
