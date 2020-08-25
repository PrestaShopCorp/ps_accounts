<?php

use PrestaShop\Module\PsAccounts\Controller\CommonApiController;
use PrestaShop\Module\PsAccounts\Repository\ServerInformationRepository;

require_once (__DIR__ . '/../../vendor/autoload.php');

class ps_AccountsApiInfoModuleFrontController extends CommonApiController
{
    public $type = 'info';

    public function postProcess()
    {
        $syncId = Tools::getValue('sync_id');

        $serverInformationRepository = new ServerInformationRepository();
        $serverInfo = $serverInformationRepository->getServerInformation();

        $this->segmentService->upload($syncId, $serverInfo);

        $this->ajaxDie(
            [
                'remaining_objects' => 0
            ]
        );
    }
}
