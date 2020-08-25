<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Db;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncStateRepository;

class AuthorizationService
{
    /**
     * @var Db
     */
    private $db;
    /**
     * @var AccountsSyncStateRepository
     */
    private $accountsSyncStateRepository;

    public function __construct(Db $db, AccountsSyncStateRepository $accountsSyncStateRepository)
    {
        $this->db = $db;
        $this->accountsSyncStateRepository = $accountsSyncStateRepository;
    }

    /**
     * Authorizes if the call to endpoint is legit and creates sync state if needed
     *
     * @param string $jobId
     * @param string $syncId
     * @param int $offset
     * @param string $endPoint
     * @return bool
     */
    public function authorizeCall($jobId, $syncId, $offset, $endPoint)
    {
        $syncState = $this->accountsSyncStateRepository->findSyncStateByJobId($jobId);

        if ($syncState) {
            return true;
        }

        //TODO: HERE WE CHECK WITH ACCOUNTS API IF JOB IS LEGIT
        if (true) {
            return $this->accountsSyncStateRepository->insertSyncState($endPoint, $jobId, $syncId, $offset);
        }

        return false;
    }
}
