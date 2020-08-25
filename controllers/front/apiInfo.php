<?php

use PrestaShop\Module\PsAccounts\Controller\CommonApiController;
use PrestaShop\Module\PsAccounts\Repository\ServerInformationRepository;

require_once (__DIR__ . '/../../vendor/autoload.php');

class ps_AccountsApiInfoModuleFrontController extends CommonApiController
{
    public $endPoint = 'apiInfo';

    public function postProcess()
    {
        $syncId = Tools::getValue('sync_id');

        $serverInformationRepository = new ServerInformationRepository();
        $serverInfo = $serverInformationRepository->getServerInformation();

        if ($this->segmentService->upload($syncId, $serverInfo)) {
            $this->segmentService->finishExport($syncId);
        }

        $this->ajaxDie($serverInfo);
    }
}
