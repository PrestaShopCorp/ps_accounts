<?php
/**
* 2007-2020 PrestaShop.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (! defined('_PS_VERSION_')) {
    exit;
}
require_once __DIR__.'/vendor/autoload.php';

class Ps_accounts extends Module
{
    public $adminControllers;
    public $author;
    public $bootstrap;
    public $css_path;
    public $description;
    public $displayName;
    public $js_path;
    public $name;
    public $ps_versions_compliancy;
    public $tab;
    public $version;
    protected $config_form = false;
    protected $tpl         = '';
    protected $tplName     = '';

    const SVC_TOKEN = "adminToken";

    /**
     * __construct.
     */
    public function __construct()
    {
        $this->name          = 'ps_accounts';
        $this->tab           = 'administration';
        $this->version       = '1.0.0';
        $this->author        = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap     = true;

        parent::__construct();

        $this->displayName = $this->l('Prestashop Account');
        $this->description = $this->l('Module Prestashop Account');

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
        $this->css_path               = $this->_path.'views/css/';
        $this->js_path                = $this->_path.'views/js/';
        $this->adminControllers       = [
            'hmac'      => 'AdminConfigureHmacPsAccounts',
            'ajax'      => 'AdminAjaxPsAccounts',
            ];
        $dotenv = new Symfony\Component\Dotenv\Dotenv();
        $dotenv->load($this->local_path.'.env');
    }

    /**
     * Load the configuration form.
     */
    public function getContent()
    {
        $say = new PrestaShop\AccountsAuth\Say();

        echo $say->hello("Hello Composer");
        die;
        $tplName = $this->dispatch();

        Media::addJsDef([
            'store' => (new PrestaShop\Module\PsAccounts\Presenter\Store\StorePresenter($this, $this->context))->present(),
        ]);
        $this->context->smarty->assign([
            'pathApp' => Tools::getShopDomainSsl(true).$this->getPathUri().'views/js/app.js',
        ]);

        return $this->context->smarty->fetch($this->local_path.'views/templates/admin/'.$this->getTplName());
    }

    /**
     * @return void
     */
    private function dispatch()
    {
        if (! $this->context->employee->isSuperAdmin()) {
            $this->setTplName('accessDenied.tpl');
            $this->setPageTitle('Access Denied');
        }

        if ($this->firstStepIsDone()) {
            $adminToken = Tools::getValue(self::SVC_TOKEN);
            $step       = Tools::getValue('step');
            // TODO emailVerified
            if ($adminToken && $step && 4 == $step) {
                $this->getRefreshTokenWithAdminToken();
                $this->setTplName('onboardingFinished.tpl');
                $this->setPageTitle('Onboarding Finished');

                return;
            }
            $token = new PrestaShop\Module\PsAccounts\Api\Firebase\Token();
            $token->refresh();

            if (! Configuration::get('PS_PSX_FIREBASE_REFRESH_TOKEN')) {
                $this->setTplName('error.tpl');
                $this->setPageTitle('FIREBASE_REFRESH_TOKEN is empty');

                return;
            }
            $this->setTplName('alreadyOnboarded.tpl');
            $this->setPageTitle('Already Onboarded');

            return;
        }

        if (Configuration::get('PS_PSX_FIREBASE_REFRESH_TOKEN')) {
            $this->setTplName('accessDenied.tpl');
            $this->setPageTitle('Access Denied');

            return;
        }
        $this->setTplName('configure.tpl');
        $this->setPageTitle('Configure');

        return;
    }

    private function firstStepIsDone()
    {
        return  Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY')
            && Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY')
            && Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA');
    }

    private function getRefreshTokenWithAdminToken()
    {
        Configuration::updateValue('PS_PSX_FIREBASE_ADMIN_TOKEN', Tools::getValue('adminToken'));
        $token = new PrestaShop\Module\PsAccounts\Api\Firebase\Token();
        $token->getRefreshTokenWithAdminToken(Tools::getValue('adminToken'));
        $token->refresh();
    }

    public function setTplName($tplName)
    {
        $this->tplName = $tplName;
    }

    public function getTplName()
    {
        return $this->tplName;
    }

    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    public function install()
    {
        return (new PrestaShop\Module\PsAccounts\Module\Install($this))->installInMenu()
            && parent::install();
    }

    public function uninstall()
    {
        return (new PrestaShop\Module\PsAccounts\Module\Uninstall($this))->uninstallMenu()
            && parent::uninstall();
    }
}
