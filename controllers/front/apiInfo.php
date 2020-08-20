<?php

use PrestaShop\Module\PsAccounts\Controller\CommonApiController;
use PrestaShop\Module\PsAccounts\Repository\ServerInformationRepository;

require_once (__DIR__ . '/../../vendor/autoload.php');

class Ps_AccountsApiInfoModuleFrontController extends CommonApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->controller_type = 'module';
    }

    public function postProcess()
    {
        $serverInformationRepository = new ServerInformationRepository();
        $serverInfo = $serverInformationRepository->getServerInformation();

        $this->ajaxDie($serverInfo);
    }
}
