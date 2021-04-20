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
     * @param int $shopId
     *
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getOrderDetails(array $orderIds, $shopId)
    {
        $query = $this->getBaseQuery();

        $query->select('od.id_order_detail, od.id_order, od.product_id, od.product_attribute_id,
         od.product_quantity, od.unit_price_tax_incl, od.unit_price_tax_excl, SUM(osd.total_price_tax_incl) as refund,
          SUM(osd.total_price_tax_excl) as refund_tax_excl, c.iso_code as currency, ps.id_category_default as category,
          l.iso_code, o.conversion_rate as conversion_rate, o.date_add as created_ad, o.date_upd as updated_at')
            ->leftJoin('order_slip_detail', 'osd', 'od.id_order_detail = osd.id_order_detail')
            ->leftJoin('product_shop', 'ps', 'od.product_id = ps.id_product AND ps.id_shop = ' . (int) $shopId)
            ->innerJoin('orders', 'o', 'od.id_order = o.id_order')
            ->leftJoin('currency', 'c', 'c.id_currency = o.id_currency')
            ->leftJoin('lang', 'l', 'o.id_lang = l.id_lang')
            ->where('od.id_order IN (' . implode(',', array_map('intval', $orderIds)) . ')')
            ->groupBy('od.id_order_detail');

        return $this->db->executeS($query);
    }
}
