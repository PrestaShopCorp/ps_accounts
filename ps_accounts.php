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
    const DEFAULT_ENV = '';

    // Needed in order to retrieve the module version easier (in api call headers) than instanciate
    // the module each time to get the version
    const VERSION = '4.0-dev';

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
    public $confirmUninstall;

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
    public $version;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * List of hook to install at the installation of the module
     *
     * @var array
     */
    private $hookToInstall = [
        'actionObjectShopUrlUpdateAfter',
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
        $this->version = '4.0-dev';

        $this->module_key = 'abf2cd758b4d629b2944d3922ef9db73';

        parent::__construct();

        $this->moduleInstaller = $this->getService(\PrestaShop\Module\PsAccounts\Installer\Installer::class);

        $this->displayName = $this->l('PrestaShop Account');

        $this->description = $this->l(
            'Link your PrestaShop account to your online shop to activate & manage services on your back-office.'
            . ' Don\'t uninstall this module if you are already using a service, as it will prevent it from working.'
        );

        $this->confirmUninstall = $this->l(
            'This action will prevent immediately your PrestaShop services and Community services from working'
            . ' as they are using PrestaShop Accounts module for authentication.'
        );

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];

        $this->adminControllers = [
            'hmac' => 'AdminConfigureHmacPsAccounts',
            'ajax' => 'AdminAjaxPsAccounts',
        ];

        $this->getLogger()->info('Loading ' . $this->name . ' Env : [' . $this->getModuleEnv() . ']');
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
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Throwable
     */
    public function install()
    {
        // if ps version is 1.7.6 or above
        if (version_compare(_PS_VERSION_, '1.7.6.0', '>=')) {
            array_push($this->hookToInstall, 'actionMetaPageSave');
        } else {
            array_push($this->hookToInstall, 'displayBackOfficeHeader');
        }

        $installer = new PrestaShop\Module\PsAccounts\Module\Install($this, Db::getInstance());

        return $installer->installInMenu()
            //&& $installer->installDatabaseTables()
            && parent::install()
            //&& $this->registerHook('addWebserviceResources')
            && $this->registerHook($this->hookToInstall)
            && $this->moduleInstaller->installModule('ps_eventbus');
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
     * Hook executed on every backoffice pages
     * Used in order to listen changes made to the AdminMeta controller
     *
     * @since 1.6
     * @deprecated since 1.7.6
     *
     * @param array $params
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        // Add a limitation in order to execute the code only if we are on the AdminMeta controller
        if ($this->context->controller->controller_name !== 'AdminMeta') {
            return false;
        }

        // If multishop is enable don't continue
        if (true === \Shop::isFeatureActive()) {
            return false;
        }

        // If a changes is make to the meta form
        if (Tools::isSubmit('submitOptionsmeta')) {
            $domain = Tools::getValue('domain'); // new domain to update
            $domainSsl = Tools::getValue('domain_ssl'); // new domain with ssl - needed ?

            $bodyHttp = [
                'params' => $params,
                'domain' => $domain,
                'domain_ssl' => $domainSsl,
            ];

            /** @var \PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService $shopLinkAccountService */
            $shopLinkAccountService = $this->getService(
                \PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService::class
            );

            $shopLinkAccountService->updateShopUrl($bodyHttp, '1.6');
        }

        return true;
    }

    /**
     * Hook executed when performing some changes to the meta page and save them
     *
     * @since 1.7.6
     *
     * @param array $params
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function hookActionMetaPageSave($params)
    {
        // If multishop is enable don't continue
        if (true === \Shop::isFeatureActive()) {
            return false;
        }

        $bodyHttp = [
            'params' => $params,
            'domain' => $params['form_data']['shop_urls']['domain'],
            'domain_ssl' => $params['form_data']['shop_urls']['domain_ssl'],
        ];

        /** @var \PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $this->getService(
            \PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService::class
        );

        $shopLinkAccountService->updateShopUrl($bodyHttp, '1.7.6');

        return true;
    }

    /**
     * Hook trigger when a change is made on the domain name
     *
     * @param array $params
     *
     * @return bool
     *
     * @throws Exception
     */
    public function hookActionObjectShopUrlUpdateAfter($params)
    {
        $bodyHttp = [
            'params' => $params,
            'domain' => $params['object']->domain,
            'domain_ssl' => $params['object']->domain_ssl,
            'shop_id' => $params['object']->id_shop,
            'main' => $params['object']->main,
            'active' => $params['object']->active,
        ];

        /** @var \PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $this->getService(
            \PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService::class
        );

        $shopLinkAccountService->updateShopUrl($bodyHttp, 'multishop');

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
        $this->context->smarty->assign('pathVendor', $this->_path . 'views/js/chunk-vendors.js');
        $this->context->smarty->assign('pathApp', $this->_path . 'views/js/app.js');

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
}
