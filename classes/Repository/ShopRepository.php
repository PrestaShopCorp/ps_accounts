<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;
use DbQuery;

class ShopRepository
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Db
     */
    private $db;

    public function __construct(Context $context, Db $db)
    {
        $this->context = $context;
        $this->db = $db;
    }

    /**
     * @return int
     */
    public function getMultiShopCount()
    {
        $query = new DbQuery();

        $query->select('COUNT(id_shop)')
            ->from('shop')
            ->where('active = 1 and deleted = 0');

        return (int) $this->db->getValue($query);
    }
}
