<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;
use DbQuery;

class CartProductRepository
{
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
        $this->db = $db;
        $this->context = $context;
    }

    /**
     * @return DbQuery
     */
    public function getBaseQuery()
    {
        $query = new DbQuery();

        $query->from('cart_product', 'cp')
            ->where('cp.id_shop = ' . (int) $this->context->shop->id);

        return $query;
    }

    /**
     * @param array $cartIds
     *
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getCartProducts(array $cartIds)
    {
        $query = $this->getBaseQuery();

        $query->select('cp.id_cart, cp.id_product, cp.id_product_attribute, cp.quantity, cp.date_add as created_at');

        if (!empty($cartIds)) {
            $query->where('cp.id_cart IN (' . implode(',', array_map('intval', $cartIds)) . ')');
        }

        return $this->db->executeS($query);
    }
}
