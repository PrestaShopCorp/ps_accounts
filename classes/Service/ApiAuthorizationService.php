<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Db;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;

class ApiAuthorizationService
{
    /**
     * @var Db
     */
    private $db;
    /**
     * @var AccountsSyncRepository
     */
    private $accountsSyncStateRepository;

    public function __construct(Db $db, AccountsSyncRepository $accountsSyncStateRepository)
    {
        $this->db = $db;
        $this->accountsSyncStateRepository = $accountsSyncStateRepository;
    }

    /**
     * Authorizes if the call to endpoint is legit and creates sync state if needed
     *
     * @param string $jobId
     * @param int $offset
     * @param string $type
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function authorizeCall($jobId)
    {
        $syncState = $this->accountsSyncStateRepository->findSyncStateByJobId($jobId);

        if ($syncState) {
            return true;
        }

        //TODO: HERE WE CHECK WITH ACCOUNTS API IF JOB IS LEGIT
        if (true) {
            return $this->accountsSyncStateRepository->insertSync($jobId, date(DATE_ATOM));
        }

        return false;
    }
}
