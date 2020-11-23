<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Service\DeletedObjectsService;

class ps_AccountsApiDeletedObjectsModuleFrontController extends AbstractApiController
{
    public function postProcess()
    {
        $jobId = Tools::getValue('job_id', '');

        /** @var DeletedObjectsService $deletedObjectsService */
        $deletedObjectsService = $this->module->getService(DeletedObjectsService::class);

        try {
            $deletedObjectsService->handleDeletedObjectsSync($jobId);
        } catch (PrestaShopDatabaseException $exception) {
            $this->exitWithExceptionMessage($exception);
        }
    }
}
