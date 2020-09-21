<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;

class CategoryRepository
{
    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param $topCategoryId
     * @param $langIso
     *
     * @return array
     */
    public function getCategoryPaths($topCategoryId, $langIso)
    {
        $categoryId = $topCategoryId;
        $categories = [];

        while ((int) $categoryId != 0) {
            $category = $this->getCategory($categoryId, $langIso);
            if (!is_array($category)) {
                break;
            }
            $categories[] = $category;
            $categoryId = $category['id_parent'];
        }

        $categories = array_reverse($categories);

        return [
            'category_path' => implode('>', array_map(function ($category) {
                return $category['name'];
            }, $categories)),
            'category_id_path' => implode('>', array_map(function ($category) {
                return $category['id_category'];
            }, $categories)),
        ];
    }

    /**
     * @param $categoryId
     * @param $langIsoCode
     *
     * @return array|bool|object|null
     */
    public function getCategory($categoryId, $langIsoCode)
    {
        $query = new DbQuery();

        $query->select('cl.name, cl.id_category, c.id_parent')
            ->from('category', 'c')
            ->innerJoin('category_lang', 'cl', 'cl.id_category = c.id_category')
            ->innerJoin('lang', 'l', 'cl.id_lang = l.id_lang AND l.iso_code = "' . pSQL($langIsoCode) . '"')
            ->where('c.id_category = ' . (int) $categoryId);

        return $this->db->getRow($query);
    }
}
