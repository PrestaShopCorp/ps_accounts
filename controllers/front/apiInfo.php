<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Repository\ServerInformationRepository;

class ps_AccountsApiInfoModuleFrontController extends AbstractApiController
{
    public $type = 'shops';

    /**
     * @throws PrestaShopException
     *
     * @return void
     */
    public function postProcess()
    {
        $jobId = Tools::getValue('job_id');

        $serverInformationRepository = $this->module->getService(ServerInformationRepository::class);

        $serverInfo = $serverInformationRepository->getServerInformation();

        $response = $this->segmentService->upload($jobId, $serverInfo);

        $this->ajaxDie(
            array_merge(['remaining_objects' => '0'], $response)
        );
    }
}
