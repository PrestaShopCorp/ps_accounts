<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Repository\ModuleRepository;

class ps_AccountsApiModulesModuleFrontController extends AbstractApiController
{
    public $type = 'modules';

    /**
     * @throws PrestaShopException
     *
     * @return void
     */
    public function postProcess()
    {
        $moduleRepository = new ModuleRepository(Db::getInstance());

        $response = $this->handleDataSync($moduleRepository);

        $this->ajaxDie($response);
    }
}
