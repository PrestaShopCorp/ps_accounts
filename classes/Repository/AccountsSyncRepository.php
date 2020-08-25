<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;

class AccountsSyncRepository
{
    const TABLE_NAME = 'accounts_sync_state';

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
            self::TABLE_NAME,
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
            self::TABLE_NAME,
            [
                'job_id' => pSQL($jobId),
                'created_at' => (int) $date,
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
            ->from('accounts_sync')
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
            ->from('accounts_type_sync')
            ->where('type = "' . pSQL($type) . '"');

        return $this->db->getRow($query);
    }
}
