<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;
use DbQuery;

class OrderDetailsRepository
{
    const TABLE_NAME = 'order_detail';

    /**
     * @var Db
     */
    private $db;
    /**
     * @var Context
     */
    private $context;

    public function __construct(Db $db, Context $context)
    {
        $this->context = $context;
        $this->db = $db;
    }

    /**
     * @return DbQuery
     */
    public function getBaseQuery()
    {
        $query = new DbQuery();

        $query->from(self::TABLE_NAME, 'od')
            ->where('od.id_shop = ' . (int) $this->context->shop->id);

        return $query;
    }

    /**
     * @param array $orderIds
     *
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getOrderDetails(array $orderIds)
    {
        $query = $this->getBaseQuery();

        $query->select('od.id_order_detail, od.id_order, od.product_id, od.product_attribute_id, od.product_quantity, od.unit_price_tax_incl')
            ->where('od.id_order IN (' . implode(',', array_map('intval', $orderIds)) . ')');

        return $this->db->executeS($query);
    }
}
