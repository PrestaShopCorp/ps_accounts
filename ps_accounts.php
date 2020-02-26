<?php
/**
* 2007-2019 PrestaShop.
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
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_')) {
    exit;
}
require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

class Ps_accounts extends Module
{
    public $name;
    public $tab;
    public $version;
    public $author;
    public $bootstrap;
    public $displayName;
    public $description;
    public $ps_versions_compliancy;
    public $css_path;
    public $js_path;
    protected $config_form = false;
    public $adminControllers;

    /**
     * __construct.
     */
    public function __construct()
    {
        $this->name = 'ps_accounts';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap = false;

        parent::__construct();

        $this->displayName = $this->l('Prestashop Account');
        $this->description = $this->l('Module Prestashop Account');

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
        $this->css_path = $this->_path.'views/css/';
        $this->js_path = $this->_path.'views/js/';
        $this->adminControllers = [
            'configure' => 'ConfigurePsAccounts',
            'hmac' => 'ConfigureHmacPsAccounts',
        ];
        $dotenv = new Dotenv();
        $dotenv->load($this->local_path.'.env');
    }

    /**
     * Load the configuration form.
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink($this->adminControllers['configure'])
        );
    }

    public function getPath()
    {
        return $this->_path;
    }
}
