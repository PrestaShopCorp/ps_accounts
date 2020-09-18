<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Decorator\ProductDecorator;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Provider\ProductDataProvider;
use PrestaShop\Module\PsAccounts\Repository\CategoryRepository;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\CurrencyRepository;
use PrestaShop\Module\PsAccounts\Repository\ImageRepository;
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
        $productRepository = new ProductRepository(
            Db::getInstance(),
            $this->context
        );

        $productDecorator = new ProductDecorator(
            $this->context,
            new LanguageRepository(),
            new CurrencyRepository(),
            $productRepository,
            new ArrayFormatter(),
            new ConfigurationRepository(),
            new ImageRepository(Db::getInstance()),
            new CategoryRepository(Db::getInstance())
        );

        $productDataProvider = new ProductDataProvider($productRepository, $productDecorator);

        $response = $this->handleDataSync($productDataProvider);

        $this->ajaxDie($response);
    }
}
