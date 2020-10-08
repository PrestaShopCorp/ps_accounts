<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Category;
use Context;
use Db;
use DbQuery;

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
     * @return DbQuery
     */
    public function getBaseQuery()
    {
        $query = new DbQuery();
        $query->from('category_shop', 'cs')
            ->innerJoin('category', 'c', 'cs.id_category = c.id_category')
            ->leftJoin('category_lang', 'cl', 'cl.id_category = cs.id_category')
            ->leftJoin('lang', 'l', 'l.id_lang = cl.id_lang');

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
        $categoryId = $topCategoryId;
        $categories = [];

        try {
            $categoriesWithParentsInfo = $this->getCategoriesWithParentInfo($langId, $shopId);
        } catch (\PrestaShopDatabaseException $e) {
            return [
                'category_path' => '',
                'category_id_path' => '',
            ];
        }

        if (!$this->topCategoryId) {
            $this->topCategoryId = Category::getTopCategory()->id;
        }

        while ((int) $categoryId != $this->topCategoryId) {
            foreach ($categoriesWithParentsInfo as $category) {
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
     * @return array
     *
     * @throws \PrestaShopDatabaseException
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
                throw new \PrestaShopDatabaseException('No categories found');
            }
        }

        return $this->categoryLangCache[$langId];
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $langIso
     *
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getCategories($offset, $limit, $langIso = null)
    {
        $query = $this->getBaseQuery();

        $query->select('CONCAT(cs.id_category, "-", l.iso_code) as unique_category_id, cs.id_category,
         c.id_parent, cl.name, cl.description, cl.link_rewrite, cl.meta_title, cl.meta_keywords, cl.meta_description,
         l.iso_code')
        ->where('cs.id_shop = ' . (int) $this->context->shop->id)
        ->limit($limit, $offset);

        if ($langIso !== null && is_string($langIso)) {
            $query->where('l.iso_code = "' . pSQL($langIso) . '"');
        }

        return $this->db->executeS($query);
    }

    /**
     * @param int $offset
     * @param string $langIso
     *
     * @return int
     */
    public function getRemainingCategoriesCount($offset, $langIso = null)
    {
        $query = $this->getBaseQuery()
            ->select('(COUNT(cs.id_category) - ' . (int) $offset . ') as count')
            ->where('cs.id_shop = ' . (int) $this->context->shop->id);

        if ($langIso !== null && is_string($langIso)) {
            $query->where('l.iso_code = "' . pSQL($langIso) . '"');
        }

        return (int) $this->db->getValue($query);
    }
}
