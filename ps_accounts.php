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
    const VERSION = '3.0.2';

    /**
     * @var array
     */
    const REQUIRED_TABLES = [
        'accounts_type_sync',
        'accounts_sync',
        'accounts_deleted_objects',
        'accounts_incremental_sync',
    ];

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
        'actionObjectProductDeleteAfter',
        'actionObjectCategoryDeleteAfter',
        'actionObjectProductAddAfter',
        'actionObjectProductUpdateAfter',
        'actionObjectCartAddAfter',
        'actionObjectCartUpdateAfter',
        'actionObjectOrderAddAfter',
        'actionObjectOrderUpdateAfter',
        'actionObjectCategoryAddAfter',
        'actionObjectCategoryUpdateAfter',
    ];

    /**
     * @var \PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer
     */
    private $serviceContainer;

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
        $this->version = '3.0.2';
        $this->module_key = 'abf2cd758b4d629b2944d3922ef9db73';

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Account');
        $this->description = $this->l('Link your PrestaShop account to your online shop to activate & manage services on your back-office. Don\'t uninstall this module if you are already using a service, as it will prevent it from working.');
        $this->confirmUninstall = $this->l('This action will prevent immediately your PrestaShop services and Community services from working as they are using PrestaShop Accounts module for authentication.');
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
        $this->adminControllers = [
            'hmac' => 'AdminConfigureHmacPsAccounts',
            'ajax' => 'AdminAjaxPsAccounts',
            'resetOnboarding' => 'AdminResetOnboarding',
        ];
        $this->serviceContainer = new \PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer(
            $this->name,
            $this->getLocalPath()
        );
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
        // if ps version is 1.7.6 or above
        if (version_compare(_PS_VERSION_, '1.7.6.0', '>=')) {
            array_push($this->hookToInstall, 'actionMetaPageSave');
        } else {
            array_push($this->hookToInstall, 'displayBackOfficeHeader');
        }

        $installer = new PrestaShop\Module\PsAccounts\Module\Install($this, Db::getInstance());

        return $installer->installInMenu()
            && $installer->installDatabaseTables()
            && parent::install()
            && $this->registerHook($this->hookToInstall);
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($this, Db::getInstance());

        return $uninstaller->uninstallMenu()
            && $uninstaller->uninstallDatabaseTables()
            && parent::uninstall();
    }

    /**
     * @param string $serviceName
     *
     * @return mixed
     */
    public function getService($serviceName)
    {
        return $this->serviceContainer->getService($serviceName);
    }

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
     * @throws ReflectionException
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
            $psAccountsService = new \PrestaShop\AccountsAuth\Service\PsAccountsService();
            $psAccountsService->changeUrl($bodyHttp, '1.6');
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
     * @throws ReflectionException
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
        $psAccountsService = new \PrestaShop\AccountsAuth\Service\PsAccountsService();
        $psAccountsService->changeUrl($bodyHttp, '1.7.6');

        return true;
    }

    /**
     * Hook trigger when a changement is made on the domain name
     *
     * @param array $params
     *
     * @return bool
     *
     * @throws ReflectionException
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
        $psAccountsService = new \PrestaShop\AccountsAuth\Service\PsAccountsService();
        $psAccountsService->changeUrl($bodyHttp, 'multishop');

        return true;
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectProductDeleteAfter($parameters)
    {
        $product = $parameters['object'];

        $this->insertDeletedObject(
            $product->id,
            'products',
            date(DATE_ATOM),
            $this->context->shop->id
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectCategoryDeleteAfter($parameters)
    {
        $category = $parameters['object'];

        $this->insertDeletedObject(
            $category->id,
            'categories',
            date(DATE_ATOM),
            $this->context->shop->id
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectProductAddAfter($parameters)
    {
        $product = $parameters['object'];

        $this->insertIncrementalSyncObject(
            $product->id,
            'products',
            date(DATE_ATOM),
            $this->context->shop->id,
            true
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectProductUpdateAfter($parameters)
    {
        $product = $parameters['object'];

        $this->insertIncrementalSyncObject(
            $product->id,
            'products',
            date(DATE_ATOM),
            $this->context->shop->id,
            true
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectCartAddAfter($parameters)
    {
        $cart = $parameters['object'];

        $this->insertIncrementalSyncObject(
            $cart->id,
            'carts',
            date(DATE_ATOM),
            $this->context->shop->id
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectCartUpdateAfter($parameters)
    {
        $cart = $parameters['object'];

        $this->insertIncrementalSyncObject(
            $cart->id,
            'carts',
            date(DATE_ATOM),
            $this->context->shop->id
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectOrderAddAfter($parameters)
    {
        $order = $parameters['object'];

        $this->insertIncrementalSyncObject(
            $order->id,
            'orders',
            date(DATE_ATOM),
            $this->context->shop->id
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectOrderUpdateAfter($parameters)
    {
        $order = $parameters['object'];

        $this->insertIncrementalSyncObject(
            $order->id,
            'orders',
            date(DATE_ATOM),
            $this->context->shop->id
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectCategoryUpdateAfter($parameters)
    {
        $category = $parameters['object'];

        $this->insertIncrementalSyncObject(
            $category->id,
            'categories',
            date(DATE_ATOM),
            $this->context->shop->id,
            true
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    public function hookActionObjectCategoryAddAfter($parameters)
    {
        $category = $parameters['object'];

        $this->insertIncrementalSyncObject(
            $category->id,
            'categories',
            date(DATE_ATOM),
            $this->context->shop->id,
            true
        );
    }

    /**
     * @param int $objectId
     * @param string $type
     * @param string $date
     * @param int $shopId
     * @param bool $hasMultiLang
     *
     * @return void
     */
    private function insertIncrementalSyncObject($objectId, $type, $date, $shopId, $hasMultiLang = false)
    {
        /** @var \PrestaShop\Module\PsAccounts\Repository\IncrementalSyncRepository $incrementalSyncRepository */
        $incrementalSyncRepository = $this->getService(
            \PrestaShop\Module\PsAccounts\Repository\IncrementalSyncRepository::class
        );

        /** @var \PrestaShop\Module\PsAccounts\Repository\LanguageRepository $languageRepository */
        $languageRepository = $this->getService(
            \PrestaShop\Module\PsAccounts\Repository\LanguageRepository::class
        );

        if ($hasMultiLang) {
            $languagesIsoCodes = $languageRepository->getLanguagesIsoCodes();

            foreach ($languagesIsoCodes as $languagesIsoCode) {
                $incrementalSyncRepository->insertIncrementalObject($objectId, $type, $date, $shopId, $languagesIsoCode);
            }
        } else {
            $languagesIsoCode = $languageRepository->getDefaultLanguageIsoCode();

            $incrementalSyncRepository->insertIncrementalObject($objectId, $type, $date, $shopId, $languagesIsoCode);
        }
    }

    /**
     * @param int $id
     * @param string $type
     * @param string $date
     * @param int $shopId
     *
     * @return void
     */
    private function insertDeletedObject($id, $type, $date, $shopId)
    {
        /** @var \PrestaShop\Module\PsAccounts\Repository\DeletedObjectsRepository $deletedObjectsRepository */
        $deletedObjectsRepository = $this->getService(
            \PrestaShop\Module\PsAccounts\Repository\DeletedObjectsRepository::class
        );

        /** @var \PrestaShop\Module\PsAccounts\Repository\IncrementalSyncRepository $incrementalSyncRepository */
        $incrementalSyncRepository = $this->getService(
            \PrestaShop\Module\PsAccounts\Repository\IncrementalSyncRepository::class
        );

        $deletedObjectsRepository->insertDeletedObject($id, $type, $date, $shopId);
        $incrementalSyncRepository->removeIncrementalSyncObject($type, $id);
    }
}
