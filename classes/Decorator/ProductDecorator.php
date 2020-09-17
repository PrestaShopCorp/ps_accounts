<?php

namespace PrestaShop\Module\PsAccounts\Decorator;

use Context;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\CurrencyRepository;
use PrestaShop\Module\PsAccounts\Repository\ImageRepository;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
use PrestaShop\Module\PsAccounts\Repository\ProductRepository;
use Product;

class ProductDecorator
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var LanguageRepository
     */
    private $languageRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ArrayFormatter
     */
    private $arrayFormatter;
    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;
    /**
     * @var ImageRepository
     */
    private $imageRepository;
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;

    public function __construct(
        Context $context,
        LanguageRepository $languageRepository,
        CurrencyRepository $currencyRepository,
        ProductRepository $productRepository,
        ArrayFormatter $arrayFormatter,
        ConfigurationRepository $configurationRepository,
        ImageRepository $imageRepository
    ) {
        $this->context = $context;
        $this->languageRepository = $languageRepository;
        $this->currencyRepository = $currencyRepository;
        $this->productRepository = $productRepository;
        $this->arrayFormatter = $arrayFormatter;
        $this->configurationRepository = $configurationRepository;
        $this->imageRepository = $imageRepository;
    }

    /**
     * @param array $products
     *
     * @return void
     */
    public function decorateProducts(array &$products)
    {
        $weightUnit = $this->configurationRepository->get('PS_WEIGHT_UNIT');

        if (($employees = \Employee::getEmployees()) !== false) {
            $this->context->employee = new \Employee($employees[0]['id_employee']);
        }

        foreach ($products as &$product) {
            $this->addLink($product);
            $this->addCoverImageLink($product);
            $this->addProductImageLinks($product);
            $this->addProductAttributes($product);
            $this->addProductFeatures($product);
            $this->addProductPricesTaxIncluded($product);
            $this->formatProductWeight($product, $weightUnit);
        }
    }

    /**
     * @param array $product
     *
     * @return void
     */
    private function addLink(array &$product)
    {
        $product['link'] = $this->context->link->getProductLink(
            $product,
            null,
            null,
            null,
            $this->languageRepository->getLanguageIdByIsoCode($product['iso_code']),
            $this->context->shop->id,
            $product['id_attribute']
        );
    }

    /**
     * @param array $product
     *
     * @return void
     */
    private function addCoverImageLink(array &$product)
    {
        $cover = $this->imageRepository->getProductCoverImage($product['id_product'], $this->context->shop->id);

        $product['cover'] = is_string($cover) ?
            $this->context->link->getImageLink($product['link_rewrite'], $cover, 'home_default') :
            '';
    }

    /**
     * @param array $product
     *
     * @return void
     */
    private function addProductAttributes(array &$product)
    {
        if ((int) $product['id_attribute'] === 0) {
            $product['attributes'] = '';
        } else {
            $product['attributes'] = $this->arrayFormatter->formatValueArray(
                $this->productRepository->getAttributes((int) $product['id_attribute'], $product['iso_code'])
            );
        }
    }

    /**
     * @param array $product
     * @return void
     */
    private function addProductFeatures(array &$product)
    {
        $product['features'] = $this->arrayFormatter->formatValueArray(
            $this->productRepository->getFeatures((int) $product['id_product'], $product['iso_code'])
        );
    }

    /**
     * @param array $product
     * @param string $weightUnit
     * @return void
     */
    private function formatProductWeight(array &$product, $weightUnit)
    {
        $product['weight'] = \Tools::ps_round((float) $product['weight'], 2) . " $weightUnit";
    }

    /**
     * @param array $product
     * @return void
     */
    private function addProductImageLinks(array &$product)
    {
        $product['images'] = $this->arrayFormatter->formatArray(
            array_map(function ($image) use ($product) {
                return $this->context->link->getImageLink($product['link_rewrite'], $image['id_image'], 'home_default');
            }, $this->imageRepository->getProductImages(
                $product['id_product'],
                $product['id_attribute'],
                $this->context->shop->id
            )
            )
        );
    }

    /**
     * @param array $product
     */
    private function addProductPricesTaxIncluded(array &$product)
    {
        $product['price_tax_incl'] = Product::getPriceStatic($product['id_product'], true, $product['id_attribute'], 6, null, false, false);
        $product['sale_price_tax_excl'] = Product::getPriceStatic($product['id_product'], false, $product['id_attribute'], 6, null, false, true);
        $product['sale_price_tax_incl'] = Product::getPriceStatic($product['id_product'], true, $product['id_attribute'], 6, null, false, true);
    }

    /**
     * @param array $product
     */
    private function addCategoryTree(array &$product)
    {
    }
}
