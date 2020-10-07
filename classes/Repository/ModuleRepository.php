<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Db;
use DbQuery;
use Exception;
use Module;

class ModuleRepository implements PaginatedApiRepositoryInterface
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

        return array_map(function ($module) {
            $moduleId = (string) $module['id_module'];

            unset($module['id_module']);

            $module['active'] = $module['active'] == '1';

            return [
                'id' => $moduleId,
                'collection' => 'modules',
                'properties' => $module,
            ];
        }, $modules);
    }

    /**
     * @return DbQuery
     */
    public function getBaseQuery()
    {
        $nativeModules = $this->getNativeModules();

        $query = new DbQuery();
        $query->from(self::MODULE_TABLE, 'm');

        if (!empty($nativeModules)) {
            $query->where('m.name NOT IN ("' . implode('","', $nativeModules) . '")');
        }

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

        $query->select('id_module, name, version as module_version, active')
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
        $query = $this->getBaseQuery();

        $query->select('(COUNT(id_module) - ' . (int) $offset . ') as count');

        return (int) $this->db->getValue($query);
    }

    /**
     * @return array
     */
    public function getNativeModules()
    {
        $nativeModulesResult = [];
        $moduleListXml = _PS_ROOT_DIR_ . Module::CACHE_FILE_MODULES_LIST;

        if (!file_exists($moduleListXml)) {
            $moduleListXml = _PS_ROOT_DIR_ . Module::CACHE_FILE_ALL_COUNTRY_MODULES_LIST;

            if (!file_exists($moduleListXml)) {
                return $nativeModulesResult;
            }
        }

        try {
            $nativeModules = (array) @simplexml_load_file($moduleListXml);

            if (isset($nativeModules['module'])) {
                $nativeModules = array_filter($nativeModules['module'], function ($module) {
                    return (string) $module->author == 'PrestaShop';
                });

                $nativeModulesResult = array_map(function ($module) {
                    return (string) $module->name;
                }, $nativeModules);
            } elseif (isset($nativeModules['modules'])) {
                foreach ($nativeModules['modules'] as $modules) {
                    if ($modules->attributes()['type'] == 'native') {
                        $modules = (array) $modules;

                        $nativeModulesResult = array_map(function ($module) {
                            return (string) $module['name'];
                        }, $modules['module']);

                        break;
                    }
                }
            }
        } catch (Exception $exception) {
            return $nativeModulesResult;
        }

        return $nativeModulesResult;
    }
}
