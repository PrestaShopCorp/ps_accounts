<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use PrestaShop\PrestaShop\Adapter\Entity\DbQuery;

class ModuleRepository
{
    const MODULE_TABLE_NAME = 'module';
    const MODULE_HISTORY_TABLE_NAME = 'module_history';

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
     * @return array[]
     */
    public function getFormattedModulesData($offset, $limit)
    {
        $modules = $this->getModules($offset, $limit);

        return array_map(function($key, $module) {
            return [
                'id' => (string) ($key + 1),
                'collection' => 'modules',
                'properties' => $module
            ];
        }, array_keys($modules), $modules);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array|bool|false|\mysqli_result|\PDOStatement|resource|null
     * @throws \PrestaShopDatabaseException
     */
    public function getModules($offset, $limit)
    {
        $query = new DbQuery();
        $query->select('name, version, active')
            ->from(self::MODULE_TABLE_NAME, 'm')
            ->limit($limit, $offset);

        return $this->db->executeS($query);
    }

    /**
     * @param int $offset
     * @return int
     */
    public function getRemainingModuleCount($offset)
    {
        $query = new DbQuery();
        $query->select('(COUNT(id_module) - ' . (int) $offset . ') as count')
            ->from(self::MODULE_TABLE_NAME);

        return (int) $this->db->getValue($query);
    }
}
