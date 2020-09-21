<?php

namespace PrestaShop\Module\PsAccounts\Decorator;

use Context;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\CategoryRepository;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\CurrencyRepository;
use PrestaShop\Module\PsAccounts\Repository\ImageRepository;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
use PrestaShop\Module\PsAccounts\Repository\ProductRepository;
use PrestaShopException;

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
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    public function __construct(
        Context $context,
        LanguageRepository $languageRepository,
        CurrencyRepository $currencyRepository,
        ProductRepository $productRepository,
        ArrayFormatter $arrayFormatter,
        ConfigurationRepository $configurationRepository,
        ImageRepository $imageRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->context = $context;
        $this->languageRepository = $languageRepository;
        $this->currencyRepository = $currencyRepository;
        $this->productRepository = $productRepository;
        $this->arrayFormatter = $arrayFormatter;
        $this->configurationRepository = $configurationRepository;
        $this->imageRepository = $imageRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param array $products
     *
     * @return void
     */
    public function decorateProducts(array &$products)
    {
        foreach ($products as &$product) {
            $this->addLink($product);
            $this->addCoverImageLink($product);
            $this->addProductImageLinks($product);
            $this->addProductAttributes($product);
            $this->addProductFeatures($product);
            $this->addProductPrices($product);
            $this->formatDescriptions($product);
            $this->addCategoryTree($product);
        }
    }

    /**
     * @param array $product
     *
     * @return void
     */
    private function addLink(array &$product)
    {
        try {
            $product['link'] = $this->context->link->getProductLink(
                $product,
                null,
                null,
                null,
                $this->languageRepository->getLanguageIdByIsoCode($product['iso_code']),
                $this->context->shop->id,
                $product['id_attribute']
            );
        } catch (PrestaShopException $e) {
            $product['link'] = '';
        }
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
     *
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
     *
     * @return void
     */
    private function addProductImageLinks(array &$product)
    {
        $images = $this->imageRepository->getProductImages(
            $product['id_product'],
            $product['id_attribute'],
            $this->context->shop->id
        );

        $product['images'] = $this->arrayFormatter->formatArray(
            array_map(function ($image) use ($product) {
                return $this->context->link->getImageLink($product['link_rewrite'], $image['id_image'], 'home_default');
            }, $images)
        );
    }

    /**
     * @param array $product
     *
     * @return void
     */
    private function addProductPrices(array &$product)
    {
        $product['price_tax_excl'] = (float) $product['price_tax_excl'];
        $product['price_tax_incl'] =
            (float) $this->productRepository->getPriceTaxIncluded($product['id_product'], $product['id_attribute']);
        $product['sale_price_tax_excl'] =
            (float) $this->productRepository->getSalePriceTaxExcluded($product['id_product'], $product['id_attribute']);
        $product['sale_price_tax_incl'] =
            (float) $this->productRepository->getSalePriceTaxIncluded($product['id_product'], $product['id_attribute']);
        $product['sale_date'] = $this->productRepository->getSaleDate($product['id_product'], $product['id_attribute']);
    }

    /**
     * @param array $product
     *
     * @return void
     */
    private function formatDescriptions(array &$product)
    {
        $product['description'] = base64_encode($product['description']);
        $product['description_short'] = base64_encode($product['description_short']);
    }

    /**
     * @param array $product
     *
     * @return void
     */
    private function addCategoryTree(array &$product)
    {
        $categoryPaths = $this->categoryRepository->getCategoryPaths(
            $product['id_category_default'],
            $product['iso_code']
        );

        $product['category_path'] = $categoryPaths['category_path'];
        $product['category_id_path'] = $categoryPaths['category_id_path'];
    }
}
