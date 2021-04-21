<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
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
     * @var Context
     */
    private $context;

    public function __construct(Db $db, Context $context)
    {
        $this->db = $db;
        $this->context = $context;
    }

    /**
     * @param int $shopId
     * @param string $langIso
     *
     * @return DbQuery
     */
    public function getBaseQuery($shopId, $langIso)
    {
        $query = new DbQuery();
        $query->from('category_shop', 'cs')
            ->innerJoin('category', 'c', 'cs.id_category = c.id_category')
            ->leftJoin('category_lang', 'cl', 'cl.id_category = cs.id_category')
            ->leftJoin('lang', 'l', 'l.id_lang = cl.id_lang')
            ->where('cs.id_shop = ' . (int) $shopId)
            ->where('cl.id_shop = cs.id_shop')
            ->where('l.iso_code = "' . pSQL($langIso) . '"');

        return $query;
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
        if ((int) $topCategoryId === 0) {
            return [
                'category_path' => '',
                'category_id_path' => '',
            ];
        }

        $categories = [];

        try {
            $categoriesWithParentsInfo = $this->getCategoriesWithParentInfo($langId, $shopId);
        } catch (PrestaShopDatabaseException $e) {
            return [
                'category_path' => '',
                'category_id_path' => '',
            ];
        }

        $this->buildCategoryPaths($categoriesWithParentsInfo, $topCategoryId, $categories);

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
     * @param array $categoriesWithParentsInfo
     * @param int $currentCategoryId
     * @param array $categories
     *
     * @return void
     */
    private function buildCategoryPaths($categoriesWithParentsInfo, $currentCategoryId, &$categories)
    {
        foreach ($categoriesWithParentsInfo as $category) {
            if ($category['id_category'] == $currentCategoryId) {
                $categories[] = $category;
                $this->buildCategoryPaths($categoriesWithParentsInfo, $category['id_parent'], $categories);
            }
        }
    }

    /**
     * @param int $langId
     * @param int $shopId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getCategoriesWithParentInfo($langId, $shopId)
    {
        if (!isset($this->categoryLangCache[$langId])) {
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

            $result = $this->db->executeS($query);

            if (is_array($result)) {
                $this->categoryLangCache[$langId] = $result;
            } else {
                throw new PrestaShopDatabaseException('No categories found');
            }
        }

        return $this->categoryLangCache[$langId];
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $langIso
     *
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     *
     * @throws PrestaShopDatabaseException
     */
    public function getCategories($offset, $limit, $langIso)
    {
        $query = $this->getBaseQuery($this->context->shop->id, $langIso);

        $this->addSelectParameters($query);

        $query->limit($limit, $offset);

        return $this->db->executeS($query);
    }

    /**
     * @param int $offset
     * @param string $langIso
     *
     * @return int
     */
    public function getRemainingCategoriesCount($offset, $langIso)
    {
        $query = $this->getBaseQuery($this->context->shop->id, $langIso)
            ->select('(COUNT(cs.id_category) - ' . (int) $offset . ') as count');

        return (int) $this->db->getValue($query);
    }

    /**
     * @param int $limit
     * @param string $langIso
     *
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     *
     * @throws PrestaShopDatabaseException
     */
    public function getCategoriesIncremental($limit, $langIso)
    {
        $query = $this->getBaseQuery($this->context->shop->id, $langIso);

        $this->addSelectParameters($query);

        $query->innerJoin('accounts_incremental_sync', 'aic', 'aic.id_object = cs.id_category AND aic.id_shop = cs.id_shop AND aic.type = "categories" and aic.lang_iso = "' . pSQL($langIso) . '"')
            ->limit($limit);

        return $this->db->executeS($query);
    }

    /**
     * @param DbQuery $query
     *
     * @return void
     */
    private function addSelectParameters(DbQuery $query)
    {
        $query->select('CONCAT(cs.id_category, "-", l.iso_code) as unique_category_id, cs.id_category,
         c.id_parent, cl.name, cl.description, cl.link_rewrite, cl.meta_title, cl.meta_keywords, cl.meta_description,
         l.iso_code, c.date_add as created_at, c.date_upd as updated_at');
    }
}
