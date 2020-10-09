<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;
use DbQuery;

class OrderRepository
{
    const MODULE_TABLE = 'orders';

    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @return DbQuery
     */
    public function getBaseQuery()
    {
        $query = new DbQuery();
        $query->from(self::MODULE_TABLE, 'o');

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
    public function getOrders($offset, $limit, $shopId)
    {
        $query = $this->getBaseQuery();
        $query->select('o.id_order, o.reference, o.id_customer, o.id_cart, o.current_state,
         o.conversion_rate, o.total_paid_tax_incl, o.date_add as created_at, o.date_upd as updated_at')
            ->where('o.id_shop = ' . (int) $shopId)
            ->limit((int) $limit, (int) $offset);

        return $this->db->executeS($query);
    }

    /**
     * @param int $offset
     * @param int $shopId
     * @return int
     */
    public function getRemainingOrderCount($offset, $shopId)
    {
        $query = $this->getBaseQuery();

        $query->select('(COUNT(id_order) - ' . (int) $offset . ') as count')
            ->where('o.id_shop = ' . (int) $shopId);

        return (int) $this->db->getValue($query);
    }
}
