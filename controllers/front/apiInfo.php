<?php

class ps_AccountsApiInfoModuleFrontController extends ModuleFrontController
{
    public $type = 'shops';

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
                'apiInfo',
                [
                    'job_id' => Tools::getValue('job_id', ''),
                ],
                null,
                null,
                $this->context->shop->id
            ));
        }
    }
}
