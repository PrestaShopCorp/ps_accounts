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
     * @param string $langIso
     * @param int $langId
     *
     * @throws \PrestaShopDatabaseException
     *
     * @return void
     */
    public function decorateProducts(array &$products, $langIso, $langId)
    {
        $this->addFeatureValues($products, $langId);
        $this->addAttributeValues($products, $langId);
        $this->addImages($products);

        foreach ($products as &$product) {
            $this->addLanguageIsoCode($product, $langIso);
            $this->addUniqueId($product);
            $this->addAttributeId($product);
            $this->addLink($product);
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
        $product['default_category'] = (string) $product['default_category'];
        $product['isbn'] = isset($product['isbn']) ? (string) $product['isbn'] : '';
        $product['ean'] = (string) $product['ean'];
        $product['upc'] = (string) $product['upc'];
        $product['is_default_attribute'] = $product['id_attribute'] === 0 || (int) $product['is_default_attribute'] === 1;
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

    /**
     * @param array $product
     *
     * @return void
     */
    private function addAttributeId(array &$product)
    {
        $product['id_product_attribute'] = "{$product['id_product']}-{$product['id_attribute']}";
    }

    /**
     * @param array $product
     * @param string $langiso
     *
     * @return void
     */
    private function addLanguageIsoCode(&$product, $langiso)
    {
        $product['iso_code'] = $langiso;
    }

    /**
     * @param array $products
     * @param int $langId
     *
     * @throws \PrestaShopDatabaseException
     *
     * @return void
     */
    private function addFeatureValues(array &$products, $langId)
    {
        $productIds = $this->arrayFormatter->formatValueArray($products, 'id_product', true);
        $features = $this->productRepository->getProductFeatures($productIds, $langId);

        foreach ($products as &$product) {
            $product['features'] = isset($features[$product['id_product']]) ? $features[$product['id_product']] : '';
        }
    }

    /**
     * @param array $products
     * @param int $langId
     *
     * @throws \PrestaShopDatabaseException
     *
     * @return void
     */
    private function addAttributeValues(array &$products, $langId)
    {
        $attributeIds = $this->arrayFormatter->formatValueArray($products, 'id_attribute', true);
        $attributes = $this->productRepository->getProductAttributeValues($attributeIds, $langId);

        foreach ($products as &$product) {
            $product['attributes'] = isset($attributes[$product['id_attribute']]) ? $attributes[$product['id_attribute']] : '';
        }
    }

    /**
     * @param array $products
     *
     * @throws \PrestaShopDatabaseException
     *
     * @return void
     */
    private function addImages(array &$products)
    {
        $productIds = $this->arrayFormatter->formatValueArray($products, 'id_product', true);
        $attributeIds = $this->arrayFormatter->formatValueArray($products, 'id_attribute', true);

        $images = $this->productRepository->getProductImages($productIds);
        $attributeImages = $this->productRepository->getAttributeImages($attributeIds);

        foreach ($products as &$product) {
            $coverImageId = '0';

            $productImages = array_filter($images, function ($image) use ($product) {
                return $image['id_product'] === $product['id_product'];
            });

            foreach ($productImages as $productImage) {
                if ($productImage['cover'] === '1') {
                    $coverImageId = $productImage['id_image'];
                    break;
                }
            }

            if ($product['id_attribute'] === '0') {
                $productImageIds = $this->arrayFormatter->formatValueArray($productImages, 'id_image');
            } else {
                $productAttributeImages = array_filter($attributeImages, function ($image) use ($product) {
                    return $image['id_product_attribute'] === $product['id_attribute'];
                });

                $productImageIds = $this->arrayFormatter->formatValueArray($productAttributeImages, 'id_image');
            }

            $productImageIds = array_diff($productImageIds, [$coverImageId]);

            $product['images'] = $this->arrayFormatter->arrayToString(
                array_map(function ($imageId) use ($product) {
                    return $this->context->link->getImageLink($product['link_rewrite'], (string) $imageId);
                }, $productImageIds)
            );

            $product['cover'] = $coverImageId == '0' ?
                '' :
                $this->context->link->getImageLink($product['link_rewrite'], (string) $coverImageId);
        }
    }
}
