<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use DateTime;
use Db;
use DbQuery;
use Employee;
use Exception;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShopDatabaseException;
use Product;
use SpecificPrice;

class ProductRepository
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
     * @var ArrayFormatter
     */
    private $arrayFormatter;

    public function __construct(Db $db, Context $context, ArrayFormatter $arrayFormatter)
    {
        $this->db = $db;
        $this->context = $context;

        if (!$this->context->employee instanceof Employee) {
            if (($employees = Employee::getEmployees()) !== false) {
                $this->context->employee = new Employee($employees[0]['id_employee']);
            }
        }
        $this->arrayFormatter = $arrayFormatter;
    }

    /**
     * @param int $shopId
     * @param int $langId
     *
     * @return DbQuery
     */
    private function getBaseQuery($shopId, $langId)
    {
        $query = new DbQuery();

        $query->from('product', 'p')
            ->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . (int) $shopId)
            ->innerJoin('product_lang', 'pl', 'pl.id_product = ps.id_product AND pl.id_shop = ps.id_shop AND pl.id_lang = ' . (int) $langId)

            ->leftJoin('product_attribute_shop', 'pas', 'pas.id_product = p.id_product AND pas.id_shop = ps.id_shop')
            ->leftJoin('product_attribute', 'pa', 'pas.id_product_attribute = pa.id_product_attribute')
            ->leftJoin('category_lang', 'cl', 'ps.id_category_default = cl.id_category AND ps.id_shop = cl.id_shop AND cl.id_lang = ' . (int) $langId)
            ->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = IFNULL(pas.id_product_attribute, 0) AND sa.id_shop = ps.id_shop')
            ->leftJoin('manufacturer', 'm', 'p.id_manufacturer = m.id_manufacturer');

        return $query;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param int $langId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getProducts($offset, $limit, $langId)
    {
        $query = $this->getBaseQuery($this->context->shop->id, $langId);

        $this->addSelectParameters($query);

        $query->limit($limit, $offset);

        $result = $this->db->executeS($query);

        return is_array($result) ? $result : [];
    }

    /**
     * @param int $offset
     * @param int $langId
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     */
    public function getRemainingProductsCount($offset, $langId)
    {
        $products = $this->getProducts($offset, 1, $langId);

        if (!is_array($products) || empty($products)) {
            return 0;
        }

        return count($products);
    }

    /**
     * @param array $attributeIds
     * @param int $langId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getProductAttributeValues(array $attributeIds, $langId)
    {
        $query = new DbQuery();

        $query->select('pas.id_product_attribute, IFNULL(GROUP_CONCAT(DISTINCT agl.name, ":", al.name SEPARATOR ";"), "") as value')
            ->from('product_attribute_shop', 'pas')
            ->leftJoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = pas.id_product_attribute')
            ->leftJoin('attribute', 'a', 'a.id_attribute = pac.id_attribute')
            ->leftJoin('attribute_group_lang', 'agl', 'agl.id_attribute_group = a.id_attribute_group AND agl.id_lang = ' . (int) $langId)
            ->leftJoin('attribute_lang', 'al', 'al.id_attribute = pac.id_attribute AND al.id_lang = agl.id_lang')
            ->where('pas.id_product_attribute IN (' . $this->arrayFormatter->arrayToString($attributeIds, ',') . ') AND pas.id_shop = ' . (int) $this->context->shop->id)
            ->groupBy('pas.id_product_attribute');

        $attributes = $this->db->executeS($query);

        if (is_array($attributes)) {
            $resultArray = [];

            foreach ($attributes as $attribute) {
                $resultArray[$attribute['id_product_attribute']] = $attribute['value'];
            }

            return $resultArray;
        }

        return [];
    }

    /**
     * @param array $productIds
     * @param int $langId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getProductFeatures(array $productIds, $langId)
    {
        $query = new DbQuery();

        $query->select('fp.id_product, IFNULL(GROUP_CONCAT(DISTINCT fl.name, ":", fvl.value SEPARATOR ";"), "") as value')
            ->from('feature_product', 'fp')
            ->leftJoin('feature_lang', 'fl', 'fl.id_feature = fp.id_feature AND fl.id_lang = ' . (int) $langId)
            ->leftJoin('feature_value_lang', 'fvl', 'fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = fl.id_lang')
            ->where('fp.id_product IN (' . $this->arrayFormatter->arrayToString($productIds, ',') . ')')
            ->groupBy('fp.id_product');

        $features = $this->db->executeS($query);

        if (is_array($features)) {
            $resultArray = [];

            foreach ($features as $feature) {
                $resultArray[$feature['id_product']] = $feature['value'];
            }

            return $resultArray;
        }

        return [];
    }

    /**
     * @param array $productIds
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getProductImages(array $productIds)
    {
        $query = new DbQuery();

        $query->select('imgs.id_product, imgs.id_image, IFNULL(imgs.cover, 0) as cover')
            ->from('image_shop', 'imgs')
            ->where('imgs.id_shop = ' . (int) $this->context->shop->id . ' AND imgs.id_product IN (' . $this->arrayFormatter->arrayToString($productIds, ',') . ')');

        $result = $this->db->executeS($query);

        return is_array($result) ? $result : [];
    }

    /**
     * @param array $attributeIds
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getAttributeImages(array $attributeIds)
    {
        $query = new DbQuery();

        $query->select('id_product_attribute, id_image')
            ->from('product_attribute_image', 'pai')
            ->where('pai.id_product_attribute IN (' . $this->arrayFormatter->arrayToString($attributeIds, ',') . ')');

        $result = $this->db->executeS($query);

        return is_array($result) ? $result : [];
    }

    /**
     * @param int $productId
     * @param int $attributeId
     *
     * @return float
     */
    public function getPriceTaxExcluded($productId, $attributeId)
    {
        return Product::getPriceStatic($productId, false, $attributeId, 6, null, false, false);
    }

    /**
     * @param int $productId
     * @param int $attributeId
     *
     * @return float
     */
    public function getPriceTaxIncluded($productId, $attributeId)
    {
        return Product::getPriceStatic($productId, true, $attributeId, 6, null, false, false);
    }

    /**
     * @param int $productId
     * @param int $attributeId
     *
     * @return float
     */
    public function getSalePriceTaxExcluded($productId, $attributeId)
    {
        return Product::getPriceStatic($productId, false, $attributeId, 6);
    }

    /**
     * @param int $productId
     * @param int $attributeId
     *
     * @return float
     */
    public function getSalePriceTaxIncluded($productId, $attributeId)
    {
        return Product::getPriceStatic($productId, true, $attributeId, 6);
    }

    /**
     * @param int $productId
     * @param int $attributeId
     *
     * @return string
     */
    public function getSaleDate($productId, $attributeId)
    {
        $specific_price = SpecificPrice::getSpecificPrice(
            (int) $productId,
            $this->context->shop->id,
            0,
            0,
            0,
            1,
            $attributeId
        );

        try {
            if (is_array($specific_price) && array_key_exists('to', $specific_price)) {
                $from = new DateTime($specific_price['from']);
                $to = new DateTime($specific_price['to']);

                return $from->format('Y-m-dTh:i-Z') . '/' . $to->format('Y-m-dTh:i-Z');
            }
        } catch (Exception $exception) {
            return '';
        }

        return '';
    }

    /**
     * @param int $limit
     * @param string $langIso
     * @param int $langId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getProductsIncremental($limit, $langIso, $langId)
    {
        $query = $this->getBaseQuery($this->context->shop->id, $langId);

        $this->addSelectParameters($query);

        $query->innerJoin('accounts_incremental_sync', 'aic', 'aic.id_object = p.id_product AND aic.id_shop = ps.id_shop AND aic.type = "products" and aic.lang_iso = "' . pSQL($langIso) . '"')
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
        $query->select('p.id_product, IFNULL(pas.id_product_attribute, 0) as id_attribute, pas.default_on as is_default_attribute,
            pl.name, pl.description, pl.description_short, pl.link_rewrite, cl.name as default_category,
            ps.id_category_default, IFNULL(pa.reference, p.reference) as reference, IFNULL(pa.upc, p.upc) as upc,
            IFNULL(pa.ean13, p.ean13) as ean, ps.condition, ps.visibility, ps.active, sa.quantity, m.name as manufacturer,
            (p.weight + IFNULL(pas.weight, 0)) as weight, (ps.price + IFNULL(pas.price, 0)) as price_tax_excl,
            p.date_add as created_at, p.date_upd as updated_at');

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $query->select('IFNULL(pa.isbn, p.isbn) as isbn');
        }
    }
}
