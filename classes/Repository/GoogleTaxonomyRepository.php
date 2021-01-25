<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;

class GoogleTaxonomyRepository
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
     * @param int $shopId
     *
     * @return DbQuery
     */
    public function getBaseQuery($shopId)
    {
        $query = new DbQuery();

        $query->from('fb_category_match', 'cm')
            ->where('cm.id_shop = ' . (int) $shopId);

        return $query;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param int $shopId
     *
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getTaxonomyCategories($offset, $limit, $shopId)
    {
        $query = $this->getBaseQuery($shopId);

        $query->select('cm.id_category, cm.google_category_id')
            ->limit($limit, $offset);

        return $this->db->executeS($query);
    }

    /**
     * @param int $offset
     * @param int $shopId
     *
     * @return int
     */
    public function getRemainingTaxonomyRepositories($offset, $shopId)
    {
        $query = $this->getBaseQuery($shopId);

        $query->select('(COUNT(cm.id_category) - ' . (int) $offset . ') as count');

        return (int) $this->db->getValue($query);
    }
}
