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

    public function authorizeCall($jobId, $syncId, $offset, $endPoint)
    {
        $syncState = $this->accountsSyncStateRepository->findSyncStateByJobId($jobId);

        if ($syncState) {
            return true;
        }

        //HERE WE CHECK WITH ACCOUNTS API IF JOB IS LEGIT
        if (true) {
            return $this->accountsSyncStateRepository->insertSyncState($endPoint, $jobId, $syncId, $offset);
        }

        return false;
    }
}
