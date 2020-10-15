<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use DbQuery;

class CartProductRepository
{
    /**
     * @return DbQuery
     */
    public function getBaseQuery()
    {
        $query = new DbQuery();

        $query->from('cart_product', 'cp');

        return $query;
    }

    public function getCartProducts(array $cartIds)
    {
        $query = $this->getBaseQuery();
    }
}
