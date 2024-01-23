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
    const VERSION = '6.4.0';

    const HOOK_ACTION_SHOP_ACCOUNT_LINK_AFTER = 'actionShopAccountLinkAfter';
    const HOOK_ACTION_SHOP_ACCOUNT_UNLINK_AFTER = 'actionShopAccountUnlinkAfter';
    const HOOK_DISPLAY_ACCOUNT_UPDATE_WARNING = 'displayAccountUpdateWarning';

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
        'displaybackOfficeEmployeeMenu',
        'displayBackOfficeHeader',
        'displayDashboardTop',
        'displayAccountUpdateWarning',
        'actionObjectShopAddAfter',
        'actionObjectShopUpdateAfter',
        'actionObjectShopDeleteBefore',
        'actionObjectShopDeleteAfter',
        'actionObjectShopUrlUpdateAfter',
        'actionModuleInstallAfter',
        //'actionAdminControllerInitBefore',
        'actionAdminLoginControllerSetMedia',
        'actionAdminLoginControllerLoginAfter',
        'actionModuleInstallAfter',
        self::HOOK_DISPLAY_ACCOUNT_UPDATE_WARNING,
        self::HOOK_ACTION_SHOP_ACCOUNT_LINK_AFTER,
        self::HOOK_ACTION_SHOP_ACCOUNT_UNLINK_AFTER,
    ];

    /**
     * List of new hooks to create at the installation of the module
     *
     * @var array
     */
    private $customHooks = [
        [
            'name' => self::HOOK_DISPLAY_ACCOUNT_UPDATE_WARNING,
            'title' => 'Display account update warning',
            'description' => 'Show a warning message when the user wants to'
                . ' update his shop configuration',
            'position' => 1,
        ],
        [
            'name' => self::HOOK_ACTION_SHOP_ACCOUNT_LINK_AFTER,
            'title' => 'Shop linked event',
            'description' => 'Shop linked with PrestaShop Account',
            'position' => 1,
        ],
        [
            'name' => self::HOOK_ACTION_SHOP_ACCOUNT_UNLINK_AFTER,
            'title' => 'Shop unlinked event',
            'description' => 'Shop unlinked with PrestaShop Account',
            'position' => 1,
        ],
    ];

    /**
     * @var \PrestaShop\Module\PsAccounts\DependencyInjection\ServiceContainer
     */
    private $serviceContainer;

    /**
     * @var \PrestaShop\Module\PsAccounts\Middleware\Oauth2Middleware
     */
    private $oauth2Middleware;

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
        $this->version = '6.4.0';

        $this->module_key = 'abf2cd758b4d629b2944d3922ef9db73';

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Account');
        $this->description = $this->l('Link your store to your PrestaShop account to activate and manage your subscriptions in your back office. Do not uninstall this module if you have a current subscription.');
        $this->description_full = $this->l('Link your store to your PrestaShop account to activate and manage your subscriptions in your back office. Do not uninstall this module if you have a current subscription.');
        $this->confirmUninstall = $this->l('This action will prevent immediately your PrestaShop services and Community services from working as they are using PrestaShop Accounts module for authentication.');

        $this->ps_versions_compliancy = ['min' => '1.6.1', 'max' => _PS_VERSION_];

        $this->adminControllers = [
            'ajax' => 'AdminAjaxPsAccounts',
            'debug' => 'AdminDebugPsAccounts',
            'oauth2' => 'AdminOAuth2PsAccounts',
            'login' => 'AdminLoginPsAccounts',
        ];
        $this->oauth2Middleware = new \PrestaShop\Module\PsAccounts\Middleware\Oauth2Middleware($this);
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
     */
    public function install()
    {
        $installer = new PrestaShop\Module\PsAccounts\Module\Install($this, Db::getInstance());

        $status = $installer->installInMenu()
            && $installer->installDatabaseTables()
            && parent::install()
            && $this->addCustomHooks($this->customHooks)
            && $this->registerHook($this->hookToInstall);

        // Removed controller
        $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($this, Db::getInstance());
        $uninstaller->deleteAdminTab('AdminConfigureHmacPsAccounts');

        if ($this->getShopContext()->isShop17()) {
            /** @var \PrestaShop\Module\PsAccounts\Installer\Installer $moduleInstaller */
            $moduleInstaller = $this->getService(\PrestaShop\Module\PsAccounts\Installer\Installer::class);

            // Ignore fail on ps_eventbus install
            $moduleInstaller->installModule('ps_eventbus');
        }

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
            && $uninstaller->uninstallDatabaseTables()
            && parent::uninstall();
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface|null
     */
    public function getCoreServiceContainer()
    {
        if (method_exists($this, 'getContainer')) {
            return $this->getContainer();
        }

        if (class_exists('\PrestaShop\PrestaShop\Adapter\SymfonyContainer')) {
            return \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        }

        return null;
    }

    /**
     * @return \PrestaShop\Module\PsAccounts\DependencyInjection\ServiceContainer
     *
     * @throws Exception
     */
    public function getServiceContainer()
    {
        if (null === $this->serviceContainer) {
            $this->serviceContainer = new \PrestaShop\Module\PsAccounts\DependencyInjection\ServiceContainer(
                // append version number to force cache generation (1.6 Core won't clear it)
                $this->name . str_replace(['.', '-'], '', $this->version),
                $this->getLocalPath(),
                $this->getModuleEnv()
            );
        }

        return $this->serviceContainer;
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
        return $this->getServiceContainer()->getService($serviceName);
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getParameter($name)
    {
        return $this->getServiceContainer()->getContainer()->getParameter($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     *
     * @throws Exception
     */
    public function hasParameter($name)
    {
        return $this->getServiceContainer()->getContainer()->hasParameter($name);
    }

    /**
     * @param array $customHooks
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addCustomHooks($customHooks)
    {
        $ret = true;

        foreach ($customHooks as $customHook) {
            $verify = true;
            if ((bool) Hook::getIdByName($customHook['name']) === false) {
                $hook = new Hook();
                $hook->name = $customHook['name'];
                $hook->title = $customHook['title'];
                $hook->description = $customHook['description'];
                $hook->position = $customHook['position'];
                $verify = $hook->add(); // return true on success
            }
            $ret = $ret && $verify;
        }

        return $ret;
    }

    /**
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookDisplaybackOfficeEmployeeMenu($params)
    {
        $bar = $params['links'];

        $link = $this->getParameter('ps_accounts.accounts_ui_url') . '?' . http_build_query([
            'utm_source' => Tools::getShopDomain(),
            'utm_medium' => 'back-office',
            'utm_campaign' => $this->name,
            'utm_content' => 'headeremployeedropdownlink',
        ]);

        $bar->add(
            new PrestaShop\PrestaShop\Core\Action\ActionsBarButton(
                '', ['link' => $link, 'icon' => 'open_in_new'], $this->l('Manage your PrestaShop account')
            )
        );
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function renderUpdateWarningView()
    {
        if ($this->getShopContext()->isShop173()) {
            /* @phpstan-ignore-next-line */
            return PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance()
                ->get('twig')
                ->render('@Modules/ps_accounts/views/templates/backoffice/update_url_warning.twig');
        } else {
            return '';
        }
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function renderDeleteWarningView()
    {
        if ($this->getShopContext()->isShop173()) {
            /* @phpstan-ignore-next-line */
            return PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance()
                ->get('twig')
                ->render('@Modules/ps_accounts/views/templates/backoffice/delete_url_warning.twig');
        } else {
            return '';
        }
    }

    /**
     * @param \PrestaShop\Module\PsAccounts\Context\ShopContext $shopContext
     * @param \PrestaShop\Module\PsAccounts\Service\PsAccountsService $accountsService
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function renderAdminShopUrlWarningIfLinked($shopContext, $accountsService)
    {
        if (!isset($_GET['updateshop_url'])) {
            return;
        }

        $shopId = $shopContext->getShopIdFromShopUrlId((int) $_GET['id_shop_url']);

        return $shopContext->execInShopContext($shopId, function () use ($accountsService) {
            if ($accountsService->isAccountLinked()) {
                return $this->renderUpdateWarningView();
            }
        });
    }

    /**
     * @param \PrestaShop\Module\PsAccounts\Context\ShopContext $shopContext
     * @param \PrestaShop\Module\PsAccounts\Service\PsAccountsService $accountsService
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function renderAdminShopWarningIfLinked($shopContext, $accountsService)
    {
        if (isset($_GET['addshop'])) {
            return;
        }

        if (isset($_GET['updateshop'])) {
            return;
        }

        /** @var \PrestaShop\Module\PsAccounts\Provider\ShopProvider $shopProvider */
        $shopProvider = $this->getService(\PrestaShop\Module\PsAccounts\Provider\ShopProvider::class);

        $shopsTree = $shopProvider->getShopsTree('ps_accounts');
        foreach ($shopsTree as $shopGroup) {
            foreach ($shopGroup['shops'] as $shop) {
                $isLink = $shopContext->execInShopContext($shop['id'], function () use ($accountsService) {
                    return $accountsService->isAccountLinked();
                });
                if ($isLink) {
                    return $this->renderDeleteWarningView();
                }
            }
        }
    }

    /**
     * @param array $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function hookDisplayDashboardTop($params)
    {
        $shopContext = $this->getShopContext();

        /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $accountsService */
        $accountsService = $this->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);

        if ('AdminShopUrl' === $_GET['controller']) {
            return $this->renderAdminShopUrlWarningIfLinked($shopContext, $accountsService);
        }

        if ('AdminShop' === $_GET['controller']) {
            return $this->renderAdminShopWarningIfLinked($shopContext, $accountsService);
        }
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function hookDisplayAccountUpdateWarning()
    {
        /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $psAccountsService */
        $psAccountsService = $this->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);

        if ($psAccountsService->isAccountLinked() && !$this->getShopContext()->isMultishopActive()) {
            // I don't load with $this->get('twig') since i had this error https://github.com/PrestaShop/PrestaShop/issues/20505
            // Some users may have the same and couldn't render the configuration page
            return $this->renderUpdateWarningView();
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     *
     * @throws Exception
     */
    public function hookActionObjectShopUrlUpdateAfter($params)
    {
        if ($params['object']->main) {
            /** @var \PrestaShop\Module\PsAccounts\Api\Client\AccountsClient $accountsApi */
            $accountsApi = $this->getService(
                \PrestaShop\Module\PsAccounts\Api\Client\AccountsClient::class
            );

            /** @var \PrestaShop\Module\PsAccounts\Adapter\Link $link */
            $link = $this->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);

            Cache::clean('Shop::setUrl_' . (int) $params['object']->id);

            $shop = new \Shop($params['object']->id);

            $domain = $params['object']->domain;
            $sslDomain = $params['object']->domain_ssl;

            $response = $accountsApi->updateUserShop(new \PrestaShop\Module\PsAccounts\DTO\UpdateShop([
                'shopId' => (string) $params['object']->id,
                'name' => $shop->name,
                'domain' => 'http://' . $domain,
                'sslDomain' => 'https://' . $sslDomain,
                'physicalUri' => $params['object']->physical_uri,
                'virtualUri' => $params['object']->virtual_uri,
                'boBaseUrl' => $link->getAdminLinkWithCustomDomain(
                    $sslDomain,
                    $domain,
                    'AdminModules',
                    false,
                    [],
                    [
                        'configure' => $this->name,
                        'setShopContext' => 's-' . $params['object']->id,
                    ]
                ),
            ]));

            if (!$response) {
                $this->getLogger()->debug(
                    'Error trying to PATCH shop : No $response object'
                );
            } elseif (true !== $response['status']) {
                $this->getLogger()->debug(
                    'Error trying to PATCH shop : ' . $response['httpCode'] .
                    ' ' . print_r($response['body']['message'] ? $response['body']['message'] : '', true)
                );
            }
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
    public function hookActionObjectShopAddAfter($params)
    {
        $this->switchConfigMultishopMode();

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     *
     * @throws Exception
     */
    public function hookActionObjectShopUpdateAfter($params)
    {
        /** @var \PrestaShop\Module\PsAccounts\Api\Client\AccountsClient $accountsApi */
        $accountsApi = $this->getService(
            \PrestaShop\Module\PsAccounts\Api\Client\AccountsClient::class
        );

        /** @var \PrestaShop\Module\PsAccounts\Adapter\Link $link */
        $link = $this->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);

        $shop = new \Shop($params['object']->id);

        $domain = $params['object']->domain;
        $sslDomain = $params['object']->domain_ssl;

        $response = $accountsApi->updateUserShop(new \PrestaShop\Module\PsAccounts\DTO\UpdateShop([
            'shopId' => (string) $params['object']->id,
            'name' => $params['object']->name,
            'domain' => 'http://' . $shop->domain,
            'sslDomain' => 'https://' . $shop->domain_ssl,
            'physicalUri' => $shop->physical_uri,
            'virtualUri' => $shop->virtual_uri,
            'boBaseUrl' => $link->getAdminLinkWithCustomDomain(
                $sslDomain,
                $domain,
                'AdminModules',
                false,
                [],
                [
                    'configure' => $this->name,
                    'setShopContext' => 's-' . $params['object']->id,
                ]
            ),
        ]));

        if (!$response) {
            $this->getLogger()->debug(
                'Error trying to PATCH shop : No $response object'
            );
        } elseif (true !== $response['status']) {
            $this->getLogger()->debug(
                'Error trying to PATCH shop : ' . $response['httpCode'] .
                ' ' . print_r($response['body']['message'] ? $response['body']['message'] : '', true)
            );
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
        $this->switchConfigMultishopMode();

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     *
     * @throws Exception
     */
    public function hookActionObjectShopDeleteBefore($params)
    {
        /** @var \PrestaShop\Module\PsAccounts\Api\Client\AccountsClient $accountsApi */
        $accountsApi = $this->getService(
            \PrestaShop\Module\PsAccounts\Api\Client\AccountsClient::class
        );

        try {
            $response = $accountsApi->deleteUserShop($params['object']->id);
            if (!$response) {
                $this->getLogger()->debug(
                    'Error trying to DELETE shop : No $response object'
                );
            } elseif (true !== $response['status']) {
                $this->getLogger()->debug(
                    'Error trying to DELETE shop : ' . $response['httpCode'] .
                    ' ' . print_r($response['body']['message'], true)
                );
            }
        } catch (Exception $e) {
            $this->getLogger()->debug(
                'Error curl while trying to DELETE shop : ' . print_r($e->getMessage(), true)
            );
        }

        return true;
    }

    /**
     * @return void
     *
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function hookDisplayBackOfficeHeader()
    {
        // Multistore On/Off switch
        /* @phpstan-ignore-next-line */
        if ('AdminPreferences' === $this->context->controller->controller_name || !$this->getShopContext()->isShop17()) {
            $this->switchConfigMultishopMode();
        }
        $this->oauth2Middleware->execute();
    }

//    /**
//     * @param array $params
//     *
//     * @return void
//     *
//     * @throws Exception
//     */
//    public function hookActionAdminControllerInitBefore($params)
//    {
//        $this->oauth2Middleware->execute();
//
//        if (Tools::getValue('mode') !== 'local') {
//            /** @var Link $link */
//            $link = $this->getService(Link::class);
//
//            Tools::redirectLink($link->getAdminLink('AdminLoginPsAccounts', false));
//        }
//    }
//    /**
//     * @param array $params
//     *
//     * @return void
//     *
//     * @throws Exception
//     */
//    public function hookActionAdminControllerInitBefore($params)
//    {
//        /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $psAccountsService */
//        $psAccountsService = $this->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);
//
//        if (isset($_GET['logout'])) {
//            if ($psAccountsService->getLoginActivated()) {
//                $this->oauth2Logout();
//            } else {
//                $this->getOauth2Session()->clear();
//            }
//        }
//    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws Exception
     */
    public function hookActionAdminLoginControllerSetMedia()
    {
        $this->oauth2Middleware->execute();

        /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $psAccountsService */
        $psAccountsService = $this->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);
        $local = Tools::getValue('mode') === AdminLoginPsAccountsController::PARAM_MODE_LOCAL ||
            !$psAccountsService->getLoginActivated();

        $this->trackLoginPage($local);

        if ($this->getShopContext()->isShop17() && !$local) {
//            /** @var \PrestaShop\Module\PsAccounts\Adapter\Link $link */
//            $link = $this->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);
//            Tools::redirectLink($link->getAdminLink('AdminLoginPsAccounts', false));
            (new AdminLoginPsAccountsController())->run();
            exit;
        }
    }

    /**
     * @param array{shopUuid: string, shopId: string} $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookActionShopAccountLinkAfter($params)
    {
        // Not implemented here
    }

    /**
     * @param array{shopUuid: string, shopId: string} $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookActionShopAccountUnlinkAfter($params)
    {
        // Not implemented here
    }

    /**
     * @param mixed $module
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookActionModuleInstallAfter($module)
    {
        $this->resetCircuitBreaker();
    }

    /**
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookActionAdminLoginControllerLoginAfter($params)
    {
        $this->trackLoginEvent($params['employee']);
    }

    /**
     * @return string
     */
    public function getModuleEnvVar()
    {
        return strtoupper((string) $this->name) . '_ENV';
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
     * @throws PrestaShopException
     * @throws \PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException
     */
    public function getContent()
    {
        $this->loadAssets();

        return $this->display(__FILE__, 'views/templates/admin/app.tpl');
    }

    /**
     * Load VueJs App and set JS variable for Vuex
     *
     * @return void
     *
     * @throws PrestaShopException
     * @throws \PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException
     */
    protected function loadAssets()
    {
        //$this->context->smarty->assign('pathVendor', $this->_path . 'views/js/chunk-vendors.' . $this->version . '.js');
        $this->context->smarty->assign('pathApp', $this->_path . 'views/js/app.' . $this->version . '.js');
        $this->context->smarty->assign('pathAppAssets', $this->_path . 'views/css/app.' . $this->version . '.css');
        $this->context->smarty->assign('urlAccountsCdn', $this->getParameter('ps_accounts.accounts_cdn_url'));

        $storePresenter = new PrestaShop\Module\PsAccounts\Presenter\Store\StorePresenter($this, $this->context);

        Media::addJsDef([
            'storePsAccounts' => $storePresenter->present(),
        ]);

        /** @var \PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter $psAccountsPresenter */
        $psAccountsPresenter = $this->getService(\PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter::class);

        Media::addJsDef([
            'contextPsAccounts' => $psAccountsPresenter->present((string) $this->name),
        ]);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function getSsoAccountUrl()
    {
        $url = $this->getParameter('ps_accounts.sso_account_url');
        $langIsoCode = $this->getContext()->language->iso_code;

        return $url . '?lang=' . substr($langIsoCode, 0, 2);
    }

    /**
     * @return \PrestaShop\Module\PsAccounts\Context\ShopContext
     *
     * @throws Exception
     */
    private function getShopContext()
    {
        return $this->getService(\PrestaShop\Module\PsAccounts\Context\ShopContext::class);
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

        if ($this->getShopContext()->isMultishopActive()) {
            $config->migrateToMultiShop();
        } else {
            $config->migrateToSingleShop();
        }
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
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

    /**
     * @return array
     */
    public function getCustomHooks()
    {
        return $this->customHooks;
    }

    /**
     * @return bool
     */
    public function isShopEdition()
    {
        return Module::isEnabled('smb_edition');
    }

    /**
     * @return \PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client
     *
     * @throws Exception
     */
    public function getOauth2Client()
    {
        return $this->getService(\PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client::class);
    }

    /**
     * @return \PrestaShop\Module\PsAccounts\Provider\OAuth2\FallbackSession
     *
     * @throws Exception
     */
    public function getSession()
    {
        $container = $this->getCoreServiceContainer();
        if ($container) {
            /** @var \PrestaShop\Module\PsAccounts\Provider\OAuth2\FallbackSession $session */
            $session = $container->get('session');

            return $session;
        } else {
            // FIXME return a session like with configuration storage
            return new \PrestaShop\Module\PsAccounts\Provider\OAuth2\FallbackSession(
                $this->getService(\PrestaShop\Module\PsAccounts\Adapter\Configuration::class)
            );
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    private function resetCircuitBreaker()
    {
        $this->getLogger()->info(__METHOD__);

        /** @var \PrestaShop\Module\PsAccounts\Api\Client\AccountsClient $accountsClient */
        $accountsClient = $this->getService(\PrestaShop\Module\PsAccounts\Api\Client\AccountsClient::class);
        $accountsClient->getCircuitBreaker()->reset();

        /** @var \PrestaShop\Module\PsAccounts\Api\Client\SsoClient $ssoClient */
        $ssoClient = $this->getService(\PrestaShop\Module\PsAccounts\Api\Client\SsoClient::class);
        $ssoClient->getCircuitBreaker()->reset();
    }

    /**
     * @param bool $local
     *
     * @return void
     *
     * @throws Exception
     */
    private function trackLoginPage($local = false)
    {
        if ($this->isShopEdition()) {
            /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $psAccountsService */
            $psAccountsService = $this->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);
            $account = $psAccountsService->getEmployeeAccount();
            $userId = $account ? $account->getUid() : null;

            /** @var \PrestaShop\Module\PsAccounts\Service\AnalyticsService $analytics */
            $analytics = $this->getService(\PrestaShop\Module\PsAccounts\Service\AnalyticsService::class);

            if (!$local) {
                $analytics->pageAccountsBoLogin($userId);
            } else {
                $analytics->pageLocalBoLogin($userId);
            }
        }
    }

    /**
     * @param Employee $employee
     *
     * @return void
     *
     * @throws Exception
     */
    private function trackLoginEvent(Employee $employee)
    {
        /** @var \PrestaShop\Module\PsAccounts\Service\AnalyticsService $analyticsService */
        $analyticsService = $this->getService(\PrestaShop\Module\PsAccounts\Service\AnalyticsService::class);

        /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $psAccountsService */
        $psAccountsService = $this->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);

        $account = $psAccountsService->getEmployeeAccount();

        if ($this->isShopEdition()) {
            $uid = null;
            if ($account) {
                $uid = $account->getUid();
                $email = $account->getEmail();
            } else {
                $email = $employee->email;
            }
            $analyticsService->identify($uid, null, $email);
            $analyticsService->group($uid, (string)$psAccountsService->getShopUuid());
            $analyticsService->trackUserSignedIntoBackOfficeLocally($uid, $email);
        }
    }
}
