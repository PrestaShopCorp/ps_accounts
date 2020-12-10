<?php

use PrestaShop\Module\PsAccounts\Config\Config;
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
        if (Module::isInstalled('ps_eventbus')) {
            Tools::redirect($this->context->link->getModuleLink(
                'ps_eventbus',
                'apiGoogleTaxonomies',
                [
                    'job_id' => Tools::getValue('job_id', ''),
                    'limit' => Tools::getValue('limit'),
                    'full' => Tools::getValue('full'),
                ],
                null,
                null,
                $this->context->shop->id
            ));
        }

        if (!Module::isInstalled('ps_facebook')) {
            $this->exitWithExceptionMessage(new Exception('Facebook module is not installed', Config::PS_FACEBOOK_NOT_INSTALLED));
        }

        $categoryDataProvider = $this->module->getService(GoogleTaxonomyDataProvider::class);

        $response = $this->handleDataSync($categoryDataProvider);

        $this->exitWithResponse($response);
    }
}
