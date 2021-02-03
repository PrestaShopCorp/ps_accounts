<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;

class ModuleRepository
{
    const MODULE_TABLE = 'module';

    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @return DbQuery
     */
    public function getBaseQuery()
    {
        $query = new DbQuery();
        $query->from(self::MODULE_TABLE, 'm');

        return $query;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array|bool|false|\mysqli_result|\PDOStatement|resource|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getModules($offset, $limit)
    {
        $query = $this->getBaseQuery();

        $query->select('id_module as module_id, name, version as module_version, active')
            ->limit($limit, $offset);

        return $this->db->executeS($query);
    }

    /**
     * @param int $offset
     *
     * @return int
     */
    public function getRemainingModules($offset)
    {
        $query = $this->getBaseQuery();

        $query->select('(COUNT(id_module) - ' . (int) $offset . ') as count');

        return (int) $this->db->getValue($query);
    }
}
