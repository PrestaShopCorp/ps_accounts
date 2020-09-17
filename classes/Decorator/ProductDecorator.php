<?php

namespace PrestaShop\Module\PsAccounts\Decorator;

use Context;
use Image;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
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

    public function __construct(
        Context $context,
        LanguageRepository $languageRepository,
        ProductRepository $productRepository,
        ArrayFormatter $arrayFormatter,
        ConfigurationRepository $configurationRepository,
        ImageRepository $imageRepository
    ) {
        $this->context = $context;
        $this->languageRepository = $languageRepository;
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

        foreach ($products as &$product) {
            $productObject = new Product(
                $product['id_product'],
                false,
                $this->languageRepository->getLanguageIdByIsoCode($product['iso_code']),
                $this->context->shop->id,
                $this->context
            );

            $this->addLink($product);
            $this->addCoverImageLink($product);
            $this->addProductImageLinks($product);
            $this->addProductAttributes($product);
            $this->addProductFeatures($product);
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
    private function formatProductWeight(&$product, $weightUnit)
    {
        $product['weight'] = \Tools::ps_round((float) $product['weight'], 2) . " $weightUnit";
    }

    /**
     * @param array $product
     * @return void
     */
    private function addProductImageLinks(&$product)
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
}
