<?php

use PrestaShop\Module\PsAccounts\Controller\CommonApiController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\CurrencyRepository;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
use PrestaShop\Module\PsAccounts\Repository\ServerInformationRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

class ps_AccountsApiInfoModuleFrontController extends CommonApiController
{
    public $type = 'info';

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

        $serverInformationRepository = new ServerInformationRepository(
            new CurrencyRepository(),
            new LanguageRepository(),
            new ConfigurationRepository()
        );

        $serverInfo = $serverInformationRepository->getServerInformation();

        $response = $this->segmentService->upload($syncId, $serverInfo);

        $this->ajaxDie(
            array_merge(['remaining_objects' => '0'], $response)
        );
    }
}
