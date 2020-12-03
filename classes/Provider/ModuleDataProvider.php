<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Repository\ModuleRepository;
use PrestaShopDatabaseException;

class ModuleDataProvider implements PaginatedApiDataProviderInterface
{
    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    public function __construct(ModuleRepository $moduleRepository)
    {
        $this->moduleRepository = $moduleRepository;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $langIso
     *
     * @return array
     */
    public function getFormattedData($offset, $limit, $langIso)
    {
        try {
            $modules = $this->moduleRepository->getModules($offset, $limit);
        } catch (PrestaShopDatabaseException $e) {
            return [];
        }

        if (!is_array($modules)) {
            return [];
        }

        return array_map(function ($module) {
            $moduleId = (string) $module['module_id'];
            $module['active'] = $module['active'] == '1';

            return [
                'id' => $moduleId,
                'collection' => 'modules',
                'properties' => $module,
            ];
        }, $modules);
    }

    /**
     * @param int $offset
     * @param string $langIso
     *
     * @return int
     */
    public function getRemainingObjectsCount($offset, $langIso)
    {
        return (int) $this->moduleRepository->getRemainingModules($offset);
    }

    public function getFormattedDataIncremental($limit, $langIso = null)
    {
        return [
            'ids' => [],
            'data' => [],
        ];
    }
}
