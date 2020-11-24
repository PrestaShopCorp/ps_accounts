<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Context;
use PrestaShop\Module\PsAccounts\Repository\DeletedObjectsRepository;

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
     * @var SegmentService
     */
    private $segmentService;

    public function __construct(Context $context, DeletedObjectsRepository $deletedObjectsRepository, SegmentService $segmentService)
    {
        $this->context = $context;
        $this->deletedObjectsRepository = $deletedObjectsRepository;
        $this->segmentService = $segmentService;
    }

    /**
     * @param string $jobId
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
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

        $response = $this->segmentService->delete($jobId, $data);

        if ($response['httpCode'] == 201) {
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
                'total_objects' => count($data),
            ],
            $data
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
