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
     * @param int $offset
     * @param int $limit
     *
     * @return array|bool|false|\mysqli_result|\PDOStatement|resource|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getModules($offset, $limit)
    {
        $query = new \DbQuery();
        $query->select('id_module, name, version as module_version, active')
            ->from(self::MODULE_TABLE, 'm')
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
        $query = new DbQuery();
        $query->select('(COUNT(id_module) - ' . (int) $offset . ') as count')
            ->from(self::MODULE_TABLE);

        return (int) $this->db->getValue($query);
    }
}
