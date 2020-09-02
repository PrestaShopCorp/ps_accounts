<?php

use PrestaShop\Module\PsAccounts\Controller\CommonApiController;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;
use PrestaShop\Module\PsAccounts\Repository\ModuleRepository;

class ps_AccountsApiModulesModuleFrontController extends CommonApiController
{
    public $type = 'modules';

    /**
     * @throws PrestaShopException
     *
     * @return void
     */
    public function postProcess()
    {
        if (!$syncId = Tools::getValue('sync_id')) {
            $this->exitWithErrorStatus();
        }

        $moduleRepository = new ModuleRepository(Db::getInstance());
        $accountsSyncRepository = new AccountsSyncRepository(Db::getInstance());

        $limit = (int) Tools::getValue('limit', 50);
        $dateNow = (new DateTime())->format(DateTime::ATOM);
        $offset = 0;

        if ($typeSync = $accountsSyncRepository->findTypeSync($this->type) !== false) {
            $offset = (int) $typeSync['offset'];
        } else {
            $accountsSyncRepository->insertTypeSync($this->type, 0, $dateNow);
        }

        $moduleInfo = $moduleRepository->getFormattedModulesData($offset, $limit);

        $response = $this->segmentService->upload($syncId, $moduleInfo);

        if ($response['httpCode'] == 201) {
            $offset += $limit;
        }

        $remainingObjects = $moduleRepository->getRemainingModuleCount($offset);

        if ($remainingObjects <= 0) {
            $remainingObjects = 0;
            $offset = 0;
        }

        $accountsSyncRepository->updateTypeSync($this->type, $offset, $dateNow);

        $this->ajaxDie(
            array_merge(
                [
                    'sync_id' => $syncId,
                    'total_objects' => count($moduleInfo),
                    'object_type' => $this->type,
                    'has_remaining_objects' => $remainingObjects > 0,
                    'remaining_objects' => $remainingObjects,
                ]
                , $response
            )
        );
    }
}
