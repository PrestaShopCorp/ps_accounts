<?php

namespace PrestaShop\Module\PsAccounts\Decorator;

use Context;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\CategoryRepository;
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
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var ArrayFormatter
     */
    private $arrayFormatter;

    public function __construct(
        Context $context,
        LanguageRepository $languageRepository,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ArrayFormatter $arrayFormatter
    ) {
        $this->context = $context;
        $this->languageRepository = $languageRepository;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->arrayFormatter = $arrayFormatter;
    }

    /**
     * @param array $products
     *
     * @return void
     */
    public function decorateProducts(array &$products)
    {
        foreach ($products as &$product) {
            $this->addUniqueId($product);
            $this->addLink($product);
            $this->addProductImageLinks($product);
            $this->addProductPrices($product);
            $this->formatDescriptions($product);
            $this->addCategoryTree($product);
            $this->castPropertyValues($product);
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
                (int) $this->languageRepository->getLanguageIdByIsoCode($product['iso_code']),
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
    private function addProductImageLinks(array &$product)
    {
        $cover = 0;
        $images = [];

        if ((string) $product['images'] !== '') {
            $productImages = explode(';', (string) $product['images']);

            $productImages = array_map(function ($image) use (&$cover) {
                $image = explode(':', $image);
                $imageId = (int) $image[0];
                $isCover = (int) $image[1];
                if ($isCover) {
                    $cover = $imageId;
                }

                return ['imageId' => $imageId, 'isCover' => $isCover];
            }, $productImages);
        } else {
            $productImages = [];
        }

        if ($product['id_attribute'] !== '0') {
            if ((string) $product['attribute_images'] !== '') {
                $attributeImages = explode(';', (string) $product['attribute_images']);
                $images = array_diff($attributeImages, [$cover]);
            } else {
                $images = [];
            }
        } else {
            foreach ($productImages as $productImage) {
                if (!$productImage['isCover']) {
                    $images[] = $productImage['imageId'];
                }
            }
        }

        $product['cover'] = $cover ?
            $this->context->link->getImageLink($product['link_rewrite'], (string) $cover, 'home_default') :
            '';

        $product['images'] = $this->arrayFormatter->formatArray(
            array_map(function ($image) use ($product) {
                return $this->context->link->getImageLink($product['link_rewrite'], (string) $image, 'home_default');
            }, $images)
        );

        unset($product['attribute_images']);
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

        $product['tax'] = $product['price_tax_incl'] - $product['price_tax_excl'];
        $product['sale_tax'] = $product['sale_price_tax_incl'] - $product['sale_price_tax_excl'];

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
            $this->languageRepository->getLanguageIdByIsoCode($product['iso_code']),
            $this->context->shop->id
        );

        $product['category_path'] = $categoryPaths['category_path'];
        $product['category_id_path'] = $categoryPaths['category_id_path'];
    }

    /**
     * @param array $product
     *
     * @return void
     */
    private function castPropertyValues(array &$product)
    {
        $product['id_product'] = (int) $product['id_product'];
        $product['id_attribute'] = (int) $product['id_attribute'];
        $product['id_category_default'] = (int) $product['id_category_default'];
        $product['quantity'] = (int) $product['quantity'];
        $product['weight'] = (float) $product['weight'];
        $product['active'] = $product['active'] == '1';
        $product['manufacturer'] = (string) $product['manufacturer'];
    }

    /**
     * @param array $product
     *
     * @return void
     */
    private function addUniqueId(array &$product)
    {
        $product['unique_product_id'] = "{$product['id_product']}-{$product['id_attribute']}-{$product['iso_code']}";
    }
}
