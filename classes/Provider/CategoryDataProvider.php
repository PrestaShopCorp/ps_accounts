<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Decorator\CategoryDecorator;
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

    public function __construct(CategoryRepository $categoryRepository, CategoryDecorator $categoryDecorator)
    {
        $this->categoryRepository = $categoryRepository;
        $this->categoryDecorator = $categoryDecorator;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string|null $langIso
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getFormattedData($offset, $limit, $langIso = null)
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
     * @param string|null $langIso
     *
     * @return int
     */
    public function getRemainingObjectsCount($offset, $langIso = null)
    {
        return $this->categoryRepository->getRemainingCategoriesCount($offset, $langIso);
    }
}
