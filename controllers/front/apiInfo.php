<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\CurrencyRepository;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
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

        $serverInformationRepository = new ServerInformationRepository(
            new CurrencyRepository(),
            new LanguageRepository(),
            new ConfigurationRepository(),
            $this->context
        );

        $serverInfo = $serverInformationRepository->getServerInformation();

        $response = $this->segmentService->upload($jobId, $serverInfo);

        $this->ajaxDie(
            array_merge(['remaining_objects' => '0'], $response)
        );
    }
}
