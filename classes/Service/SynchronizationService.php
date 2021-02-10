<?php

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Exception\EnvVarException;
use PrestaShop\Module\PsAccounts\Provider\PaginatedApiDataProviderInterface;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;
use PrestaShop\Module\PsAccounts\Repository\IncrementalSyncRepository;
use PrestaShopDatabaseException;

class SynchronizationService
{
    /**
     * @var AccountsSyncRepository
     */
    private $accountsSyncRepository;
    /**
     * @var IncrementalSyncRepository
     */
    private $incrementalSyncRepository;
    /**
     * @var ProxyService
     */
    private $proxyService;

    public function __construct(AccountsSyncRepository $accountsSyncRepository, IncrementalSyncRepository $incrementalSyncRepository, ProxyService $proxyService)
    {
        $this->accountsSyncRepository = $accountsSyncRepository;
        $this->incrementalSyncRepository = $incrementalSyncRepository;
        $this->proxyService = $proxyService;
    }

    /**
     * @param PaginatedApiDataProviderInterface $dataProvider
     * @param string $type
     * @param string $jobId
     * @param string $langIso
     * @param int $offset
     * @param int $limit
     * @param string $dateNow
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException|EnvVarException
     */
    public function handleFullSync(PaginatedApiDataProviderInterface $dataProvider, $type, $jobId, $langIso, $offset, $limit, $dateNow)
    {
        $response = [];

        $data = $dataProvider->getFormattedData($offset, $limit, $langIso);

        if (!empty($data)) {
            $response = $this->proxyService->upload($jobId, $data);

            if ($response['httpCode'] == 201) {
                $offset += $limit;
            }
        }

        $remainingObjects = (int) $dataProvider->getRemainingObjectsCount($offset, $langIso);

        if ($remainingObjects <= 0) {
            $remainingObjects = 0;
            $offset = 0;
        }

        $this->accountsSyncRepository->updateTypeSync($type, $offset, $dateNow, $remainingObjects == 0, $langIso);

        return array_merge([
            'total_objects' => count($data),
            'has_remaining_objects' => $remainingObjects > 0,
            'remaining_objects' => $remainingObjects,
            'md5' => $this->getPayloadMd5($data),
        ], $response);
    }

    /**
     * @param PaginatedApiDataProviderInterface $dataProvider
     * @param string $type
     * @param string $jobId
     * @param int $limit
     * @param string $langIso
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException|EnvVarException
     */
    public function handleIncrementalSync(PaginatedApiDataProviderInterface $dataProvider, $type, $jobId, $limit, $langIso)
    {
        $response = [];

        $incrementalData = $dataProvider->getFormattedDataIncremental($limit, $langIso);

        $objectIds = $incrementalData['ids'];
        $data = $incrementalData['data'];

        if (!empty($data)) {
            $response = $this->proxyService->upload($jobId, $data);

            if ($response['httpCode'] == 201 && !empty($objectIds)) {
                $this->incrementalSyncRepository->removeIncrementalSyncObjects($type, $objectIds, $langIso);
            }
        }

        $remainingObjects = $this->incrementalSyncRepository->getRemainingIncrementalObjects($type, $langIso);

        return array_merge([
            'total_objects' => count($data),
            'has_remaining_objects' => $remainingObjects > 0,
            'remaining_objects' => $remainingObjects,
            'md5' => $this->getPayloadMd5($data),
        ], $response);
    }

    /**
     * @param array $payload
     *
     * @return string
     */
    private function getPayloadMd5($payload)
    {
        return md5(
            implode(' ', array_map(function ($payloadItem) {
                return $payloadItem['id'];
            }, $payload))
        );
    }
}
