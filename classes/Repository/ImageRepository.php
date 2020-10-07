<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;

class ImageRepository
{
    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $productId
     * @param int $shopId
     *
     * @return false|string|null
     */
    public function getProductCoverImage($productId, $shopId)
    {
        $query = new DbQuery();

        $query->select('imgs.id_image')
            ->from('image_shop', 'imgs')
            ->where('imgs.cover = 1')
            ->where('imgs.id_shop = ' . (int) $shopId)
            ->where('imgs.id_product = ' . (int) $productId);

        return $this->db->getValue($query);
    }

    /**
     * @param int $productId
     * @param int $attributeId
     * @param int $shopId
     * @param bool $includeCover
     *
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getProductImages($productId, $attributeId, $shopId, $includeCover = false)
    {
        $query = new DbQuery();

        $query->select('imgs.id_image')
            ->from('image_shop', 'imgs')
            ->leftJoin('image', 'img', 'imgs.id_image = img.id_image')
            ->where('imgs.id_shop = ' . (int) $shopId)
            ->where('imgs.id_product = ' . (int) $productId)
            ->orderBy('img.position ASC');

        if ((int) $attributeId !== 0) {
            $query->innerJoin(
                'product_attribute_image',
                'pai',
                'imgs.id_image = pai.id_image AND pai.id_product_attribute = ' . (int) $attributeId
            );
        }

        if (!$includeCover) {
            $query->where('(imgs.cover IS NULL OR imgs.cover = 0)');
        }

        return $this->db->executeS($query);
    }
}
