<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;

class IncrementalSyncRepository
{
    const INCREMENTAL_SYNC_TABLE = 'accounts_incremental_sync';

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
     *
     * @param $shopId
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     */
    public function insertIncrementalObject($objectId, $objectType, $date, $shopId)
    {
        return $this->db->insert(
            self::INCREMENTAL_SYNC_TABLE,
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
    }

    /**
     * @param string $type
     * @param array $objectIds
     *
     * @return bool
     */
    public function removeIncrementalSyncObjects($type, $objectIds)
    {
        return $this->db->delete(
            self::INCREMENTAL_SYNC_TABLE,
            'type = "' . pSQL($type) . '"
            AND id_shop = ' . $this->context->shop->id . '
            AND id_object IN(' . implode(',', $objectIds) . ')'
        );
    }
}
