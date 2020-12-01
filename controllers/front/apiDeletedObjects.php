<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Exception\EnvVarException;
use PrestaShop\Module\PsAccounts\Service\DeletedObjectsService;

class ps_AccountsApiDeletedObjectsModuleFrontController extends AbstractApiController
{
    public $type = 'deleted';

    /**
     * @return void
     */
    public function postProcess()
    {
        $jobId = Tools::getValue('job_id', '');

        /** @var DeletedObjectsService $deletedObjectsService */
        $deletedObjectsService = $this->module->getService(DeletedObjectsService::class);

        try {
            $response = $deletedObjectsService->handleDeletedObjectsSync($jobId);
            $this->exitWithResponse($response);
        } catch (PrestaShopDatabaseException $exception) {
            $this->exitWithExceptionMessage($exception);
        } catch (EnvVarException $exception) {
            $this->exitWithExceptionMessage($exception);
        }
    }
}
