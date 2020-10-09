<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Provider\ModuleDataProvider;
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
        $moduleDataProvider = new ModuleDataProvider(
            new ModuleRepository(Db::getInstance())
        );

        $response = $this->handleDataSync($moduleDataProvider);

        $this->exitWithResponse($response);
    }
}
