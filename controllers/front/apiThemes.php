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

        $themeRepository = new ThemeRepository();

        $themeInfo = $themeRepository->getThemes();

        $response = $this->segmentService->upload($jobId, $themeInfo);

        $this->ajaxDie(
            array_merge(['remaining_objects' => '0'], $response)
        );
    }
}
