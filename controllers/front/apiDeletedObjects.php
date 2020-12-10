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
        if (Module::isInstalled('ps_eventbus')) {
            Tools::redirect($this->context->link->getModuleLink(
                'ps_eventbus',
                'apiDeletedObjects',
                [
                    'job_id' => Tools::getValue('job_id', ''),
                    'limit' => Tools::getValue('limit'),
                    'full' => Tools::getValue('full'),
                ],
                null,
                null,
                $this->context->shop->id
            ));
        }

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
