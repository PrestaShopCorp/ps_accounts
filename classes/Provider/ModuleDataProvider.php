<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Repository\ModuleRepository;
use PrestaShop\Module\PsAccounts\Repository\PaginatedApiDataProviderInterface;
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
     * @return array|void
     */
    public function getFormattedData($offset, $limit)
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
     * @param int $offset
     * @return int|void
     */
    public function getRemainingObjectsCount($offset)
    {
        return $this->moduleRepository->getRemainingModules($offset);
    }
}
