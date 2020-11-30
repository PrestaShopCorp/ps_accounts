<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Repository\ServerInformationRepository;

class ps_AccountsApiHealthCheckModuleFrontController extends AbstractApiController
{
    public $type = 'shops';

    public function init()
    {
    }

    /**
     * @return void
     */
    public function postProcess()
    {
        /** @var ServerInformationRepository $serverInformationRepository */
        $serverInformationRepository = $this->module->getService(ServerInformationRepository::class);

        $status = $serverInformationRepository->getHealthCheckData();

        $this->exitWithResponse($status);
    }
}
