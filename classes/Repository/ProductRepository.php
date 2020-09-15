<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;
use DbQuery;

class ProductRepository implements PaginatedApiRepositoryInterface
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db, Context $context)
    {
        $this->db = $db;
        $this->context = $context;
    }

    /**
     * @param $shopId
     * @return DbQuery
     */
    private function getBaseQuery($shopId)
    {
        $query = new DbQuery();
        $query->select('p.id_product, pas.id_product_attribute, CONCAT(p.id_product, "-", pas.id_product_attribute) as id, pl.name, pl.description,
         pl.description_short, l.iso_code as lang, pa.reference, pa.upc, pa.ean13 as ean, pa.isbn, ps.price + pas.price as price')
            ->from('product_attribute_shop', 'pas')
            ->leftJoin('product_attribute', 'pa', 'pas.id_product_attribute = pa.id_product_attribute')
            ->leftJoin('product_shop', 'ps', 'pas.id_product = ps.id_product')
            ->leftJoin('product', 'p', 'pas.id_product = p.id_product')
            ->leftJoin('product_lang', 'pl', 'pl.id_product = pas.id_product')
            ->leftJoin('lang', 'l', 'pl.id_lang = l.id_lang')
            ->where('pas.id_shop = ' . (int) $shopId);

        return $query;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws \PrestaShopDatabaseException
     */
    public function getFormattedData($offset, $limit)
    {
        $query = $this->getBaseQuery($this->context->shop->id);

        $query->limit($limit, $offset);

        $result = $this->db->executeS($query);


        return $result;

        // TODO: Implement getFormattedData() method.
    }

    public function getRemainingObjectsCount($offset)
    {
        // TODO: Implement getRemainingObjectsCount() method.
    }
}
