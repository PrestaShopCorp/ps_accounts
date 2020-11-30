<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Context;
use PrestaShop\Module\PsAccounts\Exception\EnvVarException;
use PrestaShop\Module\PsAccounts\Repository\DeletedObjectsRepository;
use PrestaShopDatabaseException;

class DeletedObjectsService
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var DeletedObjectsRepository
     */
    private $deletedObjectsRepository;
    /**
     * @var ProxyService
     */
    private $proxyService;

    public function __construct(Context $context, DeletedObjectsRepository $deletedObjectsRepository, ProxyService $proxyService)
    {
        $this->context = $context;
        $this->deletedObjectsRepository = $deletedObjectsRepository;
        $this->proxyService = $proxyService;
    }

    /**
     * @param string $jobId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException|EnvVarException
     */
    public function handleDeletedObjectsSync($jobId)
    {
        $deletedObjects = $this->deletedObjectsRepository->getDeletedObjectsGrouped($this->context->shop->id);

        if (empty($deletedObjects)) {
            return [
                'total_objects' => 0,
            ];
        }

        $data = $this->formatData($deletedObjects);

        $response = $this->proxyService->delete($jobId, $data);

        if ($response['httpCode'] == 200) {
            foreach ($data as $dataItem) {
                $this->deletedObjectsRepository->removeDeletedObjects(
                    $dataItem['collection'],
                    $dataItem['deleteIds'],
                    $this->context->shop->id
                );
            }
        }

        return array_merge(
            [
                'job_id' => $jobId,
                'total_objects' => count($data),
            ],
            $response
        );
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function formatData(array $data)
    {
        return array_map(function ($dataItem) {
            return [
                'collection' => $dataItem['type'],
                'deleteIds' => explode(';', $dataItem['ids']),
            ];
        }, $data);
    }
}
