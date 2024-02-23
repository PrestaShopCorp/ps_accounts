<?php

namespace PrestaShop\Module\PsAccounts\Controller;

class ModuleAdminController extends \ModuleAdminController
{
    /**
     * @var \Ps_accounts
     */
    public $module;

    public function __construct()
    {
        try {
            parent::__construct();
        } catch (\PrestaShopException $e) {

            $this->controller_type = 'moduleadmin';
            $this->controller_name = $this->getControllerName();

            $this->id = \Tab::getIdFromClassName($this->controller_name);
            $this->module = \Module::getInstanceByName('ps_accounts');
            $this->token = \Tools::getAdminToken($this->controller_name . (int) $this->id . (int) $this->context->employee->id);

//            $tab = new Tab($this->id);
//            if (!$tab->module) {
//                throw new PrestaShopException('Admin tab ' . get_class($this) . ' is not a module tab');
//            }
//
//            $this->module = Module::getInstanceByName($tab->module);
//            if (!$this->module->id) {
//                throw new PrestaShopException("Module {$tab->module} not found");
//            }
        }
    }

    public function getControllerName()
    {
        //return preg_replace('/.*?(\w+$)/', '$1', static::class);
        return preg_replace('/^.*\\\\/', '', static::class);
    }

    /**
     * @param $disable
     *
     * @return bool
     */
    public function viewAccess($disable = false)
    {
        return true;
        //return $this->access('view', $disable);
    }
}
