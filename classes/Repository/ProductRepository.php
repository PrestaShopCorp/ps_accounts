<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;
use DbQuery;
use PrestaShop\Module\PsAccounts\Decorator\ProductDecorator;

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
    /**
     * @var ProductDecorator
     */
    private $productDecorator;

    public function __construct(Db $db, Context $context, ProductDecorator $productDecorator)
    {
        $this->db = $db;
        $this->context = $context;
        $this->productDecorator = $productDecorator;
    }

    /**
     * @param $shopId
     * @return DbQuery
     */
    private function getBaseQueryWithAttributes($shopId)
    {
        $query = new DbQuery();

        $query->select('p.id_product, COALESCE(pas.id_product_attribute, 0) as id_attribute,
            pl.name, pl.description, pl.description_short, l.iso_code, cl.name as default_category, pl.link_rewrite,
            COALESCE(pa.reference, p.reference) as reference, COALESCE(pa.upc, p.upc) as upc,
            COALESCE(pa.ean13, p.ean13) as ean, COALESCE(pa.isbn, p.isbn) as isbn,
            ps.condition, ps.visibility, ps.active, sa.quantity, m.name as manufacturer,
            (p.weight + COALESCE(pas.weight, 0)) as weight')
            ->from('product_shop', 'ps')
            ->leftJoin('product', 'p', 'ps.id_product = p.id_product')
            ->leftJoin('product_attribute_shop', 'pas', 'pas.id_product = ps.id_product AND ps.id_shop = ' . (int)$shopId)
            ->leftJoin('product_attribute', 'pa', 'pas.id_product_attribute = pa.id_product_attribute')
            ->leftJoin('product_lang', 'pl', 'pl.id_product = ps.id_product')
            ->leftJoin('lang', 'l', 'pl.id_lang = l.id_lang')
            ->leftJoin('category_lang', 'cl', 'ps.id_category_default = cl.id_category AND cl.id_lang = pl.id_lang')
            ->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = COALESCE(pas.id_product_attribute, 0) AND sa.id_shop = ' . (int)$shopId)
            ->leftJoin('manufacturer', 'm', 'p.id_manufacturer = m.id_manufacturer')
            ->where('ps.id_shop = ' . (int)$shopId)
            ->orderBy('p.id_product, pas.id_product_attribute');

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
        $query = $this->getBaseQueryWithAttributes($this->context->shop->id);

        $query->limit($limit, $offset);

        $result = $this->db->executeS($query);

        $this->productDecorator->decorateProducts($result);

        return $result;
    }

    public function getRemainingObjectsCount($offset)
    {
        return 0;
    }
}
