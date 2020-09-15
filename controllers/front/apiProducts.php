<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Repository\ProductRepository;

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
        $productRepository = new ProductRepository(Db::getInstance(), $this->context);

        $response = $this->handleDataSync($productRepository);

        $this->ajaxDie($response);
    }
}
