<?php

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Api\Client\EventBusSyncClient;
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
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     */
    public function authorizeCall($jobId)
    {
        $syncState = $this->accountsSyncStateRepository->findSyncStateByJobId($jobId);

        if ($syncState) {
            return true;
        }

        if ($this->eventBusSyncClient->validateJobId($jobId)) {
            return $this->accountsSyncStateRepository->insertSync($jobId, date(DATE_ATOM));
        }

        return false;
    }
}
