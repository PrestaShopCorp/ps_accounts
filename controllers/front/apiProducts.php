<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Decorator\ProductDecorator;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
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
        $productDecorator = new ProductDecorator(
            $this->context,
            new LanguageRepository()
        );

        $productRepository = new ProductRepository(
            Db::getInstance(),
            $this->context,
            $productDecorator
        );

        $response = $this->handleDataSync($productRepository);

        $this->ajaxDie($response);
    }
}
