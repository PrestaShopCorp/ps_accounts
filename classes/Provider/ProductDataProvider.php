<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Decorator\ProductDecorator;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
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
    /**
     * @var LanguageRepository
     */
    private $languageRepository;
    /**
     * @var ArrayFormatter
     */
    private $arrayFormatter;

    public function __construct(
        ProductRepository $productRepository,
        ProductDecorator $productDecorator,
        LanguageRepository $languageRepository,
        ArrayFormatter $arrayFormatter
    ) {
        $this->productRepository = $productRepository;
        $this->productDecorator = $productDecorator;
        $this->languageRepository = $languageRepository;
        $this->arrayFormatter = $arrayFormatter;
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
    public function getFormattedData($offset, $limit, $langIso)
    {
        $langId = $this->languageRepository->getLanguageIdByIsoCode($langIso);

        $products = $this->productRepository->getProducts($offset, $limit, $langId);

        $this->productDecorator->decorateProducts($products, $langIso, $langId);

        return array_map(function ($product) {
            return [
                'id' => $product['unique_product_id'],
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
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getRemainingObjectsCount($offset, $langIso)
    {
        $langId = $this->languageRepository->getLanguageIdByIsoCode($langIso);

        return (int) $this->productRepository->getRemainingProductsCount($offset, $langId);
    }

    /**
     * @param int $limit
     * @param string $langIso
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getFormattedDataIncremental($limit, $langIso)
    {
        $langId = $this->languageRepository->getLanguageIdByIsoCode($langIso);

        $products = $this->productRepository->getProductsIncremental($limit, $langIso, $langId);

        $productIds = $this->separateProductIds($products, count($products) < $limit);

        if (!empty($products)) {
            $this->productDecorator->decorateProducts($products, $langIso, $langId);
        }

        $data = array_map(function ($product) {
            return [
                'id' => $product['unique_product_id'],
                'collection' => 'products',
                'properties' => $product,
            ];
        }, $products);

        return [
            'data' => $data,
            'ids' => $productIds,
        ];
    }

    /**
     * @param array $products
     * @param bool $includeLast
     *
     * @return array
     */
    private function separateProductIds($products, $includeLast)
    {
        $productIds = $this->arrayFormatter->formatValueArray($products, 'id_product', true);

        if (!$includeLast) {
            array_pop($productIds);
        }

        return $productIds;
    }
}
