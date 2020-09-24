<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Decorator\ProductDecorator;
use PrestaShop\Module\PsAccounts\Repository\PaginatedApiDataProviderInterface;
use PrestaShop\Module\PsAccounts\Repository\ProductRepository;

class ProductDataProvider implements PaginatedApiDataProviderInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ProductDecorator
     */
    private $productDecorator;

    public function __construct(ProductRepository $productRepository, ProductDecorator $productDecorator)
    {
        $this->productRepository = $productRepository;
        $this->productDecorator = $productDecorator;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $langIso
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getFormattedData($offset, $limit, $langIso = null)
    {
        $products = $this->productRepository->getProducts($offset, $limit, $langIso);

        $this->productDecorator->decorateProducts($products);

        return array_map(function ($product) {
            return [
                'id' => "{$product['id_product']}-{$product['id_attribute']}-{$product['iso_code']}",
                'collection' => 'products',
                'properties' => $product,
            ];
        }, $products);
    }

    /**
     * @param int $offset
     * @param string $langIso
     *
     * @return int
     */
    public function getRemainingObjectsCount($offset, $langIso = null)
    {
        return $this->productRepository->getRemainingProductsCount($offset, $langIso);
    }
}
