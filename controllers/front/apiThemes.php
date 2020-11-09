<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Repository\ThemeRepository;

class ps_AccountsApiThemesModuleFrontController extends AbstractApiController
{
    public $type = 'themes';

    /**
     * @throws PrestaShopException
     *
     * @return void
     */
    public function postProcess()
    {
        $jobId = Tools::getValue('job_id');

        $themeRepository = $this->module->getService(ThemeRepository::class);

        $themeInfo = $themeRepository->getThemes();

        $response = $this->proxyService->upload($jobId, $themeInfo);

        $this->exitWithResponse(array_merge(['remaining_objects' => '0'], $response));
    }
}
