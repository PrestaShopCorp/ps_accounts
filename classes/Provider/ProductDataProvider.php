<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Decorator\ProductDecorator;
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

    public function __construct(
        ProductRepository $productRepository,
        ProductDecorator $productDecorator,
        LanguageRepository $languageRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productDecorator = $productDecorator;
        $this->languageRepository = $languageRepository;
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

        return $this->productRepository->getRemainingProductsCount($offset, $langId);
    }

    /**
     * @param array $products
     *
     * @return array
     */
    private function separateProductIds($products)
    {
        $productIds = [];
        $productId = 0;

        foreach ($products as $product) {
            if ($productId !== (int) $product['id_product']) {
                $productIds[] = $productId;
                $productId = (int) $product['id_product'];
            }
        }

        return $productIds;
    }
}
