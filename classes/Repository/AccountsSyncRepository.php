<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;

class AccountsSyncRepository
{
    const TYPE_SYNC_TABLE_NAME = 'accounts_type_sync';
    const SYNC_TABLE_NAME = 'accounts_sync';

    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param $type
     * @param $offset
     * @param $lastSyncDate
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function insertTypeSync($type, $offset, $lastSyncDate)
    {
        return $this->db->insert(
            self::TYPE_SYNC_TABLE_NAME,
            [
                'type' => pSQL($type),
                'offset' => (int) $offset,
                'last_sync_date' => pSQL($lastSyncDate)
            ]
        );
    }

    /**
     * @param $jobId
     * @param $date
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function insertSync($jobId, $date)
    {
        return $this->db->insert(
            self::SYNC_TABLE_NAME,
            [
                'job_id' => pSQL($jobId),
                'created_at' => pSQL($date),
            ]
        );
    }

    /**
     * @param $jobId
     * @return array|bool|false|object|null
     */
    public function findSyncStateByJobId($jobId)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::SYNC_TABLE_NAME)
            ->where('job_id = "' . pSQL($jobId) . '"');

        return $this->db->getRow($query);
    }

    /**
     * @param $type
     * @return array|bool|false|object|null
     */
    public function findSyncType($type)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::TYPE_SYNC_TABLE_NAME)
            ->where('type = "' . pSQL($type) . '"');

        return $this->db->getRow($query);
    }
}
