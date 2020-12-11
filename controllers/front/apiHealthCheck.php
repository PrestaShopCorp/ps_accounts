<?php

class ps_AccountsApiHealthCheckModuleFrontController extends ModuleFrontController
{
    public function init()
    {
    }

    /**
     * @return void
     */
    public function postProcess()
    {
        if (Module::isInstalled('ps_eventbus')) {
            Tools::redirect($this->context->link->getModuleLink(
                'ps_eventbus',
                'apiHealthCheck',
                [],
                null,
                null,
                $this->context->shop->id
            ));
        }
    }
}
