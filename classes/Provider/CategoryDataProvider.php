<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Decorator\CategoryDecorator;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\CategoryRepository;

class CategoryDataProvider implements PaginatedApiDataProviderInterface
{
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var CategoryDecorator
     */
    private $categoryDecorator;
    /**
     * @var ArrayFormatter
     */
    private $arrayFormatter;

    public function __construct(CategoryRepository $categoryRepository, CategoryDecorator $categoryDecorator, ArrayFormatter $arrayFormatter)
    {
        $this->categoryRepository = $categoryRepository;
        $this->categoryDecorator = $categoryDecorator;
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
        $categories = $this->categoryRepository->getCategories($offset, $limit, $langIso);

        if (!is_array($categories)) {
            return [];
        }

        $this->categoryDecorator->decorateCategories($categories);

        return array_map(function ($category) {
            return [
                'id' => "{$category['id_category']}-{$category['iso_code']}",
                'collection' => 'categories',
                'properties' => $category,
            ];
        }, $categories);
    }

    /**
     * @param int $offset
     * @param string $langIso
     *
     * @return int
     */
    public function getRemainingObjectsCount($offset, $langIso)
    {
        return (int) $this->categoryRepository->getRemainingCategoriesCount($offset, $langIso);
    }

    public function getFormattedDataIncremental($limit, $langIso)
    {
        $categories = $this->categoryRepository->getCategoriesIncremental($limit, $langIso);

        if (!is_array($categories)) {
            return [];
        }

        $categoryIds = $this->separateCategoryIds($categories);

        $this->categoryDecorator->decorateCategories($categories);

        $categories = array_map(function ($category) {
            return [
                'id' => "{$category['id_category']}-{$category['iso_code']}",
                'collection' => 'categories',
                'properties' => $category,
            ];
        }, $categories);

        return [
            'data' => $categories,
            'ids' => $categoryIds,
        ];
    }

    /**
     * @param array $categories
     *
     * @return array
     */
    private function separateCategoryIds(array $categories)
    {
        return $this->arrayFormatter->formatValueArray($categories, 'id_category', true);
    }
}
