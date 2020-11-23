<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;

class DeletedObjectsRepository
{
    const DELETED_OBJECTS_TABLE = 'deleted_objects';

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
     * @param int $objectId
     * @param string $objectType
     * @param string $date
     * @param int $shopId
     *
     * @return bool
     */
    public function insertDeletedObject($objectId, $objectType, $date, $shopId)
    {
        try {
            return $this->db->insert(
                self::DELETED_OBJECTS_TABLE,
                [
                    'id_shop' => $shopId,
                    'id_object' => $objectId,
                    'type' => $objectType,
                    'created_at' => $date,
                ],
                false,
                true,
                Db::ON_DUPLICATE_KEY
            );
        } catch (\PrestaShopDatabaseException $e) {
            return false;
        }
    }

    /**
     * @param string $type
     * @param array $objectIds
     *
     * @return bool
     */
    public function removeDeletedObjects($type, $objectIds)
    {
        return $this->db->delete(
            self::DELETED_OBJECTS_TABLE,
            'type = "' . pSQL($type) . '"
            AND id_shop = ' . $this->context->shop->id . '
            AND id_object IN(' . implode(',', $objectIds) . ')'
        );
    }
}
