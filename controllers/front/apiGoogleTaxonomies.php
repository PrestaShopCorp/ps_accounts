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
            $this->exitWithResponse(
                [
                    'total_objects' => 0,
                    'has_remaining_objects' => false,
                    'remaining_objects' => 0,
                    'job_id' => Tools::getValue('job_id'),
                    'object_type' => $this->type,
                ]
            );
        }

        $categoryDataProvider = $this->module->getService(GoogleTaxonomyDataProvider::class);

        $response = $this->handleDataSync($categoryDataProvider);

        $this->exitWithResponse($response);
    }
}
