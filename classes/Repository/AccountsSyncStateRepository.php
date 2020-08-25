<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;

class AccountsSyncStateRepository
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
     * @param $jobId
     * @param $offset
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function insertSyncState($type, $jobId, $offset)
    {
        return $this->db->insert(
            self::TABLE_NAME,
            [
                'type' => pSQL($type),
                'job_id' => pSQL($jobId),
                'offset' => (int) $offset
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
            ->from('accounts_sync_state')
            ->where('job_id = "' . pSQL($jobId) . '"');

        return $this->db->getRow($query);
    }
}
