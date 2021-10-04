<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
require_once __DIR__ . '/vendor/autoload.php';

class Ps_accounts extends Module
{
    const DEFAULT_ENV = '';

    // Needed in order to retrieve the module version easier (in api call headers) than instanciate
    // the module each time to get the version
    const VERSION = '5.0.3';

    /**
     * @var array
     */
    private $adminControllers;

    /**
     * List of hook to install at the installation of the module
     *
     * @var array
     */
    private $hookToInstall = [
        'displayAdminForm',
        'displayBackOfficeHeader',
        'actionObjectShopAddAfter',
        'actionObjectShopDeleteAfter',
        //'addWebserviceResources',
    ];

    /**
     * @var \PrestaShop\Module\PsAccounts\DependencyInjection\ServiceContainer
     */
    private $serviceContainer;

//    /**
//     * @var \Symfony\Component\DependencyInjection\ContainerInterface
//     */
//    protected $container;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var \PrestaShop\Module\PsAccounts\Installer\Installer
     */
    private $moduleInstaller;

    /**
     * Ps_accounts constructor.
     */
    public function __construct()
    {
        $this->name = 'ps_accounts';
        $this->tab = 'administration';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap = false;

        // We cannot use the const VERSION because the const is not computed by addons marketplace
        // when the zip is uploaded
        $this->version = '5.0.3';

        $this->module_key = 'abf2cd758b4d629b2944d3922ef9db73';

        parent::__construct();

        $this->moduleInstaller = $this->getService(\PrestaShop\Module\PsAccounts\Installer\Installer::class);

        $this->displayName = $this->l('ps_accounts.display_name');
        $this->description = $this->l('ps_accounts.description');
        $this->description_full = $this->l('ps_accounts.description_full');
        $this->confirmUninstall = $this->l('ps_accounts.confirm_uninstall');

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];

        $this->adminControllers = [
            'ajax' => 'AdminAjaxPsAccounts',
            'debug' => 'AdminDebugPsAccounts',
        ];
    }

    /**
     * @return \Monolog\Logger
     *
     * @throws Exception
     */
    public function getLogger()
    {
        return $this->getService('ps_accounts.logger');
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
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Throwable
     */
    public function install()
    {
        $installer = new PrestaShop\Module\PsAccounts\Module\Install($this, Db::getInstance());

        $status = $installer->installInMenu()
            //&& $installer->installDatabaseTables()
            && parent::install()
            && $this->registerHook($this->hookToInstall);

        // Removed controller
        $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($this, Db::getInstance());
        $uninstaller->deleteAdminTab('AdminConfigureHmacPsAccounts');

        // Ignore fail on ps_eventbus install
        $this->moduleInstaller->installModule('ps_eventbus');

        $this->switchConfigMultishopMode();

        $this->autoReonboardOnV5();

        $this->getLogger()->info('Install - Loading ' . $this->name . ' Env : [' . $this->getModuleEnv() . ']');

        return $status;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($this, Db::getInstance());

        return $uninstaller->uninstallMenu()
            //&& $uninstaller->uninstallDatabaseTables()
            && parent::uninstall();
    }

    /**
     * @param string $serviceName
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getService($serviceName)
    {
        if (null === $this->serviceContainer) {
            //$this->serviceContainer = new \PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer(
            $this->serviceContainer = new \PrestaShop\Module\PsAccounts\DependencyInjection\ServiceContainer(
                // append version number to force cache generation (1.6 Core won't clear it)
                $this->name . str_replace(['.', '-'], '', $this->version),
                $this->getLocalPath(),
                $this->getModuleEnv()
            );
        }

        return $this->serviceContainer->getService($serviceName);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->serviceContainer->getContainer()->getParameter($name);
    }

//    /**
//     * Override of native function to always retrieve Symfony container instead of legacy admin container on legacy context.
//     *
//     * @param string $serviceName
//     *
//     * @return mixed
//     */
//    public function getService($serviceName)
//    {
//        if ((new \PrestaShop\Module\PsAccounts\Context\ShopContext())->isShop173()) {
//            // 1.7.3
//            // 1.7.6
//            //$this->context->controller->getContainer()
//
//            if (null === $this->container) {
//                $this->container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
//            }
//        }
//        return $this->container->get($serviceName);
//    }

    /**
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookDisplayAdminForm($params)
    {
        $this->switchConfigMultishopMode();
    }

    /**
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        if ($this->context->controller->controller_name !== 'AdminPreferences') {
            $this->switchConfigMultishopMode();
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     *
     * @throws Exception
     */
    public function hookActionObjectShopAddAfter($params)
    {
        if ($this->context->controller->controller_name === 'AdminShop') {
            $this->switchConfigMultishopMode();
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     *
     * @throws Exception
     */
    public function hookActionObjectShopDeleteAfter($params)
    {
        if ($this->context->controller->controller_name === 'AdminShop') {
            $this->switchConfigMultishopMode();
        }

        return true;
    }

    /**
     * @return string
     */
    public function getModuleEnvVar()
    {
        return strtoupper($this->name) . '_ENV';
    }

    /**
     * @param string $default
     *
     * @return string
     */
    public function getModuleEnv($default = null)
    {
        return getenv($this->getModuleEnvVar()) ?: $default ?: self::DEFAULT_ENV;
    }

    /**
     * Load the configuration form.
     *
     * @return string
     *
     * @throws Throwable
     */
    public function getContent()
    {
        $this->loadAssets(\Tools::getValue('google_message_error'), \Tools::getValue('countProperty'));

        return $this->display(__FILE__, '/views/templates/admin/app.tpl');
    }

    /**
     * Load VueJs App and set JS variable for Vuex
     *
     * @param string $responseApiMessage
     * @param int $countProperty
     *
     * @return void
     *
     * @throws Throwable
     */
    protected function loadAssets($responseApiMessage = 'null', $countProperty = 0)
    {
        /** @var Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');
        $this->context->smarty->assign('pathVendor', $this->_path . 'views/js/chunk-vendors.js');
        $this->context->smarty->assign('pathApp', $this->_path . 'views/js/app.js');
        $this->context->smarty->assign('urlAccountsVueCdn', $module->getParameter('ps_accounts.accounts_vue_cdn_url'));

        $storePresenter = new PrestaShop\Module\PsAccounts\Presenter\Store\StorePresenter($this, $this->context);

        Media::addJsDef([
            'storePsAccounts' => $storePresenter->present(),
        ]);

        /** @var \PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter $psAccountsPresenter */
        $psAccountsPresenter = $this->getService(\PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter::class);

        Media::addJsDef([
            'contextPsAccounts' => $psAccountsPresenter->present($this->name),
        ]);
    }

    /**
     * @return string
     */
    public function getSsoAccountUrl()
    {
        $url = $this->getParameter('ps_accounts.sso_account_url');
        $langIsoCode = $this->getContext()->language->iso_code;

        return $url . '?lang=' . substr($langIsoCode, 0, 2);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    private function switchConfigMultishopMode()
    {
        /** @var \PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository $config */
        $config = $this->getService(\PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository::class);

        /** @var \PrestaShop\Module\PsAccounts\Context\ShopContext $shopContext */
        $shopContext = $this->getService(\PrestaShop\Module\PsAccounts\Context\ShopContext::class);

        if ($shopContext->isMultishopActive()) {
            $config->migrateToMultiShop();
        } else {
            $config->migrateToSingleShop();
        }
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    private function autoReonboardOnV5()
    {
        /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $psAccountsService */
        $psAccountsService = $this->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);
        $psAccountsService->autoReonboardOnV5();
    }

    /**
     * @return array
     */
    public function getHookToInstall()
    {
        return $this->hookToInstall;
    }
}
