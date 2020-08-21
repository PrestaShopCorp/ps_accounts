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

    public function insertSyncState($endpoint, $jobId, $syncId, $offset)
    {
        return $this->db->insert(
            self::TABLE_NAME,
            [
                'endpoint' => $endpoint,
                'job_id' => $jobId,
                'sync_id' => $syncId,
                'offset' => $offset
            ]
        );
    }

    public function findSyncStateByJobId($jobId)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('accounts_sync_state')
            ->where('job_id = "' . pSQL($jobId) . '"');

        return $this->db->getRow($query);
    }
}
