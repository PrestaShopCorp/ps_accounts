<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Provider\GoogleTaxonomyDataProvider;

class ps_AccountsApiGoogleTaxonomiesModuleFrontController extends AbstractApiController
{
    public $type = 'taxonomies';

    /**
     * @throws PrestaShopException
     *
     * @return void
     */
    public function postProcess()
    {
        if (!Module::isInstalled('ps_facebook')) {
            $this->exitWithExceptionMessage(new Exception('Facebook module is not installed', 500));
        }

        $categoryDataProvider = $this->module->getService(GoogleTaxonomyDataProvider::class);

        $response = $this->handleDataSync($categoryDataProvider);

        $this->exitWithResponse($response);
    }
}
