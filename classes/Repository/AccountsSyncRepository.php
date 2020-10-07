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
     * @param string $type
     * @param int $offset
     * @param string $lastSyncDate
     * @param string $langIso
     *
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     */
    public function insertTypeSync($type, $offset, $lastSyncDate, $langIso = null)
    {
        return $this->db->insert(
            self::TYPE_SYNC_TABLE_NAME,
            [
                'type' => pSQL($type),
                'offset' => (int) $offset,
                'last_sync_date' => pSQL($lastSyncDate),
                'lang_iso' => pSQL($langIso),
            ]
        );
    }

    /**
     * @param string $jobId
     * @param string $date
     *
     * @return bool
     *
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
     * @param string $jobId
     *
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
     * @param string $type
     * @param string $langIso
     *
     * @return array|bool|object|null
     */
    public function findTypeSync($type, $langIso = null)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::TYPE_SYNC_TABLE_NAME)
            ->where('type = "' . pSQL($type) . '"')
            ->where('lang_iso = ' . ($langIso == null ? 'NULL' : '"' . pSQL($langIso) . '"'));

        return $this->db->getRow($query);
    }

    /**
     * @param string $type
     * @param int $offset
     * @param string $date
     *
     * @return bool
     */
    public function updateTypeSync($type, $offset, $date)
    {
        return $this->db->update(
            self::TYPE_SYNC_TABLE_NAME,
            [
                'offset' => $offset,
                'last_sync_date' => $date,
            ],
            'type = "' . pSQL($type) . '"'
        );
    }
}
