<?php

use PrestaShop\Module\PsAccounts\Controller\CommonApiController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\CurrencyRepository;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
use PrestaShop\Module\PsAccounts\Repository\ServerInformationRepository;
use PrestaShop\Module\PsAccounts\Repository\ThemeRepository;

class ps_AccountsApiThemesModuleFrontController extends CommonApiController
{
    public $type = 'themes';

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

        $themeRepository = new ThemeRepository();

        $themeInfo = $themeRepository->getThemes();

        $response = $this->segmentService->upload($syncId, $themeInfo);

        $this->ajaxDie(
            array_merge(['remaining_objects' => '0'], $response)
        );
    }
}
