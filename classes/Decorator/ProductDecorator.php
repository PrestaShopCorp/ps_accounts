<?php

namespace PrestaShop\Module\PsAccounts\Decorator;

use Context;
use Image;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
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

    public function __construct(Context $context, LanguageRepository $languageRepository)
    {
        $this->context = $context;
        $this->languageRepository = $languageRepository;
    }

    /**
     * @param array $products
     *
     * @return void
     */
    public function decorateProducts(array &$products)
    {
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
        }
    }

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

    private function addCoverImageLink(array &$product)
    {
        $cover = Image::getCover($product['id_product']);
        $product['cover'] =
            ($cover !== false && is_array($cover)) ? $this->context->link->getImageLink($product['link_rewrite'], $cover['id_image'], 'home_default') : '';
    }

    private function addProductAttributes()
    {

    }

    private function addProductFeatures()
    {

    }
}
