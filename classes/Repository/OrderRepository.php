<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;
use PrestaShopDatabaseException;

class OrderRepository
{
    const ORDERS_TABLE = 'orders';

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
        $query->from(self::ORDERS_TABLE, 'o')
            ->leftJoin('currency', 'c', 'o.id_currency = c.id_currency')
            ->leftJoin('order_slip', 'os', 'o.id_order = os.id_order')
            ->where('o.id_shop = ' . (int) $shopId)
            ->groupBy('o.id_order');

        return $query;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param int $shopId
     *
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     *
     * @throws PrestaShopDatabaseException
     */
    public function getOrders($offset, $limit, $shopId)
    {
        $query = $this->getBaseQuery($shopId);

        $this->addSelectParameters($query);

        $query->limit((int) $limit, (int) $offset);

        return $this->db->executeS($query);
    }

    /**
     * @param int $offset
     * @param int $shopId
     *
     * @return int
     */
    public function getRemainingOrderCount($offset, $shopId)
    {
        $query = new DbQuery();

        $query->select('(COUNT(o.id_order) - ' . (int) $offset . ') as count')
            ->from(self::ORDERS_TABLE, 'o')
            ->where('o.id_shop = ' . (int) $shopId);

        return (int) $this->db->getValue($query);
    }

    /**
     * @param int $limit
     * @param int $shopId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getOrdersIncremental($limit, $shopId)
    {
        $query = $this->getBaseQuery($shopId);

        $this->addSelectParameters($query);

        $query->innerJoin(
            'accounts_incremental_sync',
            'aic',
            'aic.id_object = o.id_order AND aic.id_shop = o.id_shop AND aic.type = "orders"'
        )
            ->limit($limit);

        $result = $this->db->executeS($query);

        return is_array($result) ? $result : [];
    }

    /**
     * @param DbQuery $query
     *
     * @return void
     */
    private function addSelectParameters(DbQuery $query)
    {
        $query->select('o.id_order, o.reference, o.id_customer, o.id_cart, o.current_state,
         o.conversion_rate, o.total_paid_tax_excl, o.total_paid_tax_incl,
         IF((SELECT so.id_order FROM `' . _DB_PREFIX_ . 'orders` so WHERE so.id_customer = o.id_customer AND so.id_order < o.id_order LIMIT 1) > 0, 0, 1) as new_customer,
         c.iso_code as currency, SUM(os.total_products_tax_incl + os.total_shipping_tax_incl) as refund,
         SUM(os.total_products_tax_excl + os.total_shipping_tax_excl) as refund_tax_excl, o.module as payment_module,
         o.payment as payment_mode, o.total_paid_real, o.total_shipping as shipping_cost, o.date_add as created_at,
         o.date_upd as updated_at');
    }
}
