<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Category;
use Db;
use DbQuery;
use mysqli_result;
use PDOStatement;
use PrestaShopDatabaseException;

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
     * @param int $shopId
     *
     * @return array
     */
    public function getCategoryPaths($topCategoryId, $langId, $shopId)
    {
        $categoryId = $topCategoryId;
        $categories = [];

        if (!isset($this->categoryLangCache[$langId])) {
            try {
                $this->categoryLangCache[$langId] = $this->getCategoriesWithParentInfo($langId, $shopId);
            } catch (PrestaShopDatabaseException $e) {
                return [
                    'category_path' => '',
                    'category_id_path' => '',
                ];
            }
        }

        if (!$this->topCategoryId) {
            $this->topCategoryId = Category::getTopCategory()->id;
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

    /**
     * @param int $langId
     * @param int $shopId
     *
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     *
     * @throws PrestaShopDatabaseException
     */
    public function getCategoriesWithParentInfo($langId, $shopId)
    {
        $query = new DbQuery();

        $query->select('c.id_category, cl.name, c.id_parent')
            ->from('category', 'c')
            ->leftJoin(
                'category_lang',
                'cl',
                'cl.id_category = c.id_category AND cl.id_shop = ' . (int) $shopId
            )
            ->where('cl.id_lang = ' . (int) $langId)
            ->orderBy('cl.id_category');

        return $this->db->executeS($query);
    }
}
