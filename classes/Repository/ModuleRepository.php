<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;

class ModuleRepository implements PaginatedApiRepositoryInterface
{
    const MODULE_TABLE_NAME = 'module';

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
     * @return array[]
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getFormattedData($offset, $limit)
    {
        $modules = $this->getModules($offset, $limit);

        if (!is_array($modules)) {
            return [];
        }

        return array_map(function ($key, $module) {
            return [
                'id' => (string) ((int) $key + 1),
                'collection' => 'modules',
                'properties' => $module,
            ];
        }, array_keys($modules), $modules);
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
        $query->select('name, version, active')
            ->from(self::MODULE_TABLE_NAME, 'm')
            ->limit($limit, $offset);

        return $this->db->executeS($query);
    }

    /**
     * @param int $offset
     *
     * @return int
     */
    public function getRemainingObjectsCount($offset)
    {
        $query = new DbQuery();
        $query->select('(COUNT(id_module) - ' . (int) $offset . ') as count')
            ->from(self::MODULE_TABLE_NAME);

        return (int) $this->db->getValue($query);
    }
}
