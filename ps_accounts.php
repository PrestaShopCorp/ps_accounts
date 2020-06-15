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
if (!defined('_PS_VERSION_')) {
    exit;
}
require_once __DIR__ . '/vendor/autoload.php';

class Ps_accounts extends Module
{
    /**
     * @var array
     */
    public $adminControllers;

    /**
     * @var string
     */
    public $author;

    /**
     * @var bool
     */
    public $bootstrap;

    /**
     * @var int
     */
    public $need_instance;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $displayName;

    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $ps_versions_compliancy;

    /**
     * @var string
     */
    public $tab;

    /**
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * @var string
     */
    public $version;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * __construct.
     */
    public function __construct()
    {
        $this->name = 'ps_accounts';
        $this->tab = 'administration';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->version = self::VERSION;

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Account');
        $this->description = $this->l('Link your PrestaShop account to your online shop to activate & manage services on your back-office. Don\'t uninstall this module if you are already using a service, as it will prevent it from working.');

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
        $this->adminControllers = [
            'hmac' => 'AdminConfigureHmacPsAccounts',
            'ajax' => 'AdminAjaxPsAccounts',
            'resetOnboarding' => 'AdminResetOnboarding',
        ];
    }

    /**
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        if (null !== $this->logger) {
            return $this->logger;
        }

        $this->logger = PrestaShop\Module\PsAccounts\Factory\PsAccountsLogger::create();

        return $this->logger;
    }

    /**
     * @return \Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return array
     */
    public function getAdminControllers()
    {
        return $this->adminControllers;
    }

    /**
     * @return bool
     */
    public function install()
    {
        $hookUpdateShopDomain = 'displayBackOfficeHeader';
        // if ps version is 1.7.6 or above
        if (version_compare(_PS_VERSION_, '1.7.6.0', '>=')) {
            $hookUpdateShopDomain = 'actionMetaPageSave';
        }

        return (new PrestaShop\Module\PsAccounts\Module\Install($this))->installInMenu()
            && $this->registerHook($hookUpdateShopDomain)
            && parent::install();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return (new PrestaShop\Module\PsAccounts\Module\Uninstall($this))->uninstallMenu()
            && parent::uninstall();
    }

    /**
    * Hook executed on every backoffice pages
    * Used in order to listen changes made to the AdminMeta controller
    *
    * @since 1.6
    * @deprecated since 1.7.6
    */
    public function hookDisplayBackOfficeHeader($params)
    {
        // Add a limitation in order to execute the code only if we are on the AdminMeta controller
        if ($this->context->controller->controller_name !== 'AdminMeta') {
            return false;
        }

        // If a changes is make to the meta form
        if (Tools::isSubmit('submitOptionsmeta')) {
            $domain = Tools::getValue('domain'); // new domain to update
            $domainSsl = Tools::getValue('domain_ssl'); // new domain with ssl - needed ?

            var_dump($params);
            var_dump($domainSsl);
            var_dump($context);
            die();
        }
    }

    /**
    * Hook executed when performing some changes to the meta page and save them
    *
    * @since 1.7.6
    */
    public function hookActionMetaPageSave($params)
    {
        var_dump($params);
        var_dump($params['form_data']); // -> data with the new domain
        die();
    }
}
