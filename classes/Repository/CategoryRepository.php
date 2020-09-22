<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Category;
use Db;

class CategoryRepository
{
    /**
     * @var Db
     */
    private $db;

    /**
     * @var array
     */
    private $categoryLangCache;

    /**
     * @var int
     */
    private $topCategoryId = 0;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $topCategoryId
     * @param int $langId
     *
     * @return array
     */
    public function getCategoryPaths($topCategoryId, $langId)
    {
        $categoryId = $topCategoryId;
        $categories = [];

        if (!isset($this->categoryLangCache[$langId])) {
            $this->categoryLangCache[$langId] = Category::getSimpleCategoriesWithParentInfos($langId);
        }

        if (!$this->topCategoryId) {
            $this->topCategoryId = Category::getTopCategory($langId)->id;
        }

        while ((int) $categoryId != $this->topCategoryId) {
            foreach ($this->categoryLangCache[$langId] as $category) {
                if ($category['id_category'] == $categoryId) {
                    $categories[] = $category;
                    $categoryId = $category['id_parent'];
                    break;
                }
            }
        }

        $categories = array_reverse($categories);

        return [
            'category_path' => implode(' > ', array_map(function ($category) {
                return $category['name'];
            }, $categories)),
            'category_id_path' => implode(' > ', array_map(function ($category) {
                return $category['id_category'];
            }, $categories)),
        ];
    }
}
