<?php

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Api\EventBusSyncClient;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;

class ApiAuthorizationService
{
    /**
     * @var AccountsSyncRepository
     */
    private $accountsSyncStateRepository;
    /**
     * @var EventBusSyncClient
     */
    private $eventBusSyncClient;

    public function __construct(
        AccountsSyncRepository $accountsSyncStateRepository,
        EventBusSyncClient $eventBusSyncClient
    ) {
        $this->accountsSyncStateRepository = $accountsSyncStateRepository;
        $this->eventBusSyncClient = $eventBusSyncClient;
    }

    /**
     * Authorizes if the call to endpoint is legit and creates sync state if needed
     *
     * @param string $jobId
     *
     * @return array|bool
     *
     * @throws \PrestaShopDatabaseException
     */
    public function authorizeCall($jobId)
    {
        $syncState = $this->accountsSyncStateRepository->findSyncStateByJobId($jobId);

        if ($syncState) {
            return true;
        }

        return $this->accountsSyncStateRepository->insertSync($jobId, date(DATE_ATOM));

//        $jobValidationResponse = $this->eventBusSyncClient->validateJobId($jobId);
//
//        if (is_array($jobValidationResponse) && (int) $jobValidationResponse['httpCode'] === 201) {
//            return $this->accountsSyncStateRepository->insertSync($jobId, date(DATE_ATOM));
//        }

        return $jobValidationResponse;
    }
}
