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

if (!class_exists('\PrestaShop\Module\PsAccounts\Hook\HookableTrait')) {
    ps_accounts_fix_upgrade();
}

class Ps_accounts extends Module
{
    use \PrestaShop\Module\PsAccounts\Hook\HookableTrait;

    const DEFAULT_ENV = '';

    // Needed in order to retrieve the module version easier (in api call headers) than instanciate
    // the module each time to get the version
<<<<<<< HEAD
    const VERSION = '7.0.2';
=======
    const VERSION = '6.3.0';

    const HOOK_ACTION_SHOP_ACCOUNT_LINK_AFTER = 'actionShopAccountLinkAfter';
    const HOOK_ACTION_SHOP_ACCOUNT_UNLINK_AFTER = 'actionShopAccountUnlinkAfter';
    const HOOK_DISPLAY_ACCOUNT_UPDATE_WARNING = 'displayAccountUpdateWarning';
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

    /**
     * Admin tabs
     *
     * @var array class names
     */
    private $adminControllers = [
        'AdminAjaxPsAccountsController',
        'AdminDebugPsAccountsController',
        'AdminOAuth2PsAccountsController',
        'AdminLoginPsAccountsController',
    ];

    /**
     * Hooks exposed by the module
     *
     * @var array
     */
    private $customHooks = [
        [
            'name' => 'displayAccountUpdateWarning',
            'title' => 'Display account update warning',
            'description' => 'Show a warning message when the user wants to'
                . ' update his shop configuration',
            'position' => 1,
        ],
        [
            'name' => 'actionShopAccountLinkAfter',
            'title' => 'Shop linked event',
            'description' => 'Shop linked with PrestaShop Account',
            'position' => 1,
        ],
        [
            'name' => 'actionShopAccountUnlinkAfter',
            'title' => 'Shop unlinked event',
            'description' => 'Shop unlinked with PrestaShop Account',
            'position' => 1,
        ],
        [
            'name' => 'actionShopAccessTokenRefreshAfter',
            'title' => 'Shop access token refreshed event',
            'description' => 'Shop access token refreshed event',
            'position' => 1,
        ],
    ];

    /**
     * Hooks to register
     *
     * @var array hook or class names
     */
    private $hooks = [
        //\PrestaShop\Module\PsAccounts\Hook\ActionAdminLoginControllerLoginAfter::class,
        'actionAdminLoginControllerLoginAfter',
        'actionObjectEmployeeDeleteAfter',
        'actionObjectShopAddAfter',
        'actionObjectShopDeleteAfter',
        'actionObjectShopDeleteBefore',
        'actionObjectShopUpdateAfter',
        'actionObjectShopUrlUpdateAfter',
        'actionShopAccountLinkAfter',
        'actionShopAccountUnlinkAfter',
        'displayAccountUpdateWarning',
        'displayBackOfficeEmployeeMenu',
        'displayDashboardTop',

        // toggle single/multi-shop
//        'actionObjectShopAddAfter',
//        'actionObjectShopDeleteAfter',

        // Login/Logout OAuth
        // PS 1.6 - 1.7
        'displayBackOfficeHeader',
        'actionAdminLoginControllerSetMedia',
        // PS >= 8
//        'actionAdminControllerInitBefore',
    ];

    /**
     * @var \PrestaShop\Module\PsAccounts\DependencyInjection\ServiceContainer
     */
    private $serviceContainer;

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
<<<<<<< HEAD
        $this->version = '7.0.2';
=======
        $this->version = '6.3.0';
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        $this->module_key = 'abf2cd758b4d629b2944d3922ef9db73';

        parent::__construct();

        $this->displayName = $this->l(
            'PrestaShop Account'
        );
        $this->description = $this->l(
            'Link your store to your PrestaShop account to activate and manage your subscriptions in your ' .
            'back office. Do not uninstall this module if you have a current subscription.'
        );
        $this->description_full = $this->l(
            'Link your store to your PrestaShop account to activate and manage your subscriptions in your ' .
            'back office. Do not uninstall this module if you have a current subscription.'
        );
        $this->confirmUninstall = $this->l(
            'This action will prevent immediately your PrestaShop services and Community services from ' .
            'working as they are using PrestaShop Accounts module for authentication.'
        );

        $this->ps_versions_compliancy = ['min' => '1.6.1', 'max' => _PS_VERSION_];
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
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
<<<<<<< HEAD
     * @throws Exception
=======
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     */
    public function install()
    {
        $installer = new PrestaShop\Module\PsAccounts\Module\Install($this, Db::getInstance());

        $status = $installer->installInMenu()
            && $installer->installDatabaseTables()
            && parent::install()
            && $this->addCustomHooks($this->customHooks)
            && $this->registerHook($this->getHooksToRegister());

<<<<<<< HEAD
        $this->onModuleReset();
=======
        // Removed controller
        $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($this, Db::getInstance());
        $uninstaller->deleteAdminTab('AdminConfigureHmacPsAccounts');

        /** @var \PrestaShop\Module\PsAccounts\Installer\Installer $moduleInstaller */
        $moduleInstaller = $this->getService(\PrestaShop\Module\PsAccounts\Installer\Installer::class);

        // Ignore fail on ps_eventbus install
        $moduleInstaller->installModule('ps_eventbus');

        $this->switchConfigMultishopMode();

        $this->autoReonboardOnV5();
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        $this->getLogger()->info('Install - Loading ' . $this->name . ' Env : [' . $this->getModuleEnv() . ']');

        return $status;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
            // append version number to force cache generation (1.6 Core won't clear it)
            $this->serviceContainer = new \PrestaShop\Module\PsAccounts\DependencyInjection\ServiceContainer(
                $this->name . str_replace(['.', '-', '+'], '', $this->version),
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
     * @return array
     */
    public function getAdminControllers()
    {
        return array_map(function ($className) {
            return preg_replace('/^.*?(\w+)Controller$/', '\1', $className);
        //return preg_replace('/^(.*?)Controller$/', '\1', $className);
        }, $this->adminControllers);
    }

    /**
     * @return array
     */
    public function getHooksToRegister()
    {
        return array_map(function ($className) {
            return is_a($className, '\PrestaShop\Module\PsAccounts\Hook\Hook', true) ?
                $className::getName() : $className;
        }, $this->hooks);
    }

    /**
     * @return array
     */
    public function getCustomHooks()
    {
        return $this->customHooks;
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
<<<<<<< HEAD
=======
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
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        // Multistore On/Off switch
        /* @phpstan-ignore-next-line */
        if ('AdminPreferences' === $this->context->controller->controller_name) {
            $this->switchConfigMultishopMode();
        }
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function renderUpdateWarningView()
    {
        /* @phpstan-ignore-next-line */
        return PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance()
            ->get('twig')
            ->render('@Modules/ps_accounts/views/templates/backoffice/update_url_warning.twig');
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function renderDeleteWarningView()
    {
        /* @phpstan-ignore-next-line */
        return PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance()
            ->get('twig')
            ->render('@Modules/ps_accounts/views/templates/backoffice/delete_url_warning.twig');
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
            return null;
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
            return null;
        }

        if (isset($_GET['updateshop'])) {
            return null;
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
            /** @var \PrestaShop\Module\PsAccounts\Adapter\Link $link */
            $link = $this->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);

            Cache::clean('Shop::setUrl_' . (int) $params['object']->id);

            $shop = new \Shop($params['object']->id);

            $domain = $params['object']->domain;
            $sslDomain = $params['object']->domain_ssl;

            $response = $this->getCommandBus()->handle(
                new \PrestaShop\Module\PsAccounts\Domain\Shop\Command\UpdateShopCommand(
                    new \PrestaShop\Module\PsAccounts\Dto\UpdateShop([
                        'shopId' => (string) $params['object']->id,
                        'name' => $shop->name,
                        'domain' => $domain,
                        'sslDomain' => $sslDomain,
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
                    ])
                )
            );

            if (!$response) {
                $this->getLogger()->debug(
                    'Error trying to PATCH shop : No $response object'
                );
            } elseif (true !== $response['status']) {
                $this->getLogger()->debug(
                    'Error trying to PATCH shop : ' . $response['httpCode'] .
                    ' ' . print_r($response['body']['message'] ?? '', true)
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
        /** @var \PrestaShop\Module\PsAccounts\Adapter\Link $link */
        $link = $this->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);

        $shop = new \Shop($params['object']->id);

        $domain = $params['object']->domain;
        $sslDomain = $params['object']->domain_ssl;

        $response = $this->getCommandBus()->handle(
            new \PrestaShop\Module\PsAccounts\Domain\Shop\Command\UpdateShopCommand(
                new \PrestaShop\Module\PsAccounts\Dto\UpdateShop([
                    'shopId' => (string) $params['object']->id,
                    'name' => $params['object']->name,
                    'domain' => $shop->domain,
                    'sslDomain' => $shop->domain_ssl,
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
                ])
            )
        );

        if (!$response) {
            $this->getLogger()->debug(
                'Error trying to PATCH shop : No $response object'
            );
        } elseif (true !== $response['status']) {
            $this->getLogger()->debug(
                'Error trying to PATCH shop : ' . $response['httpCode'] .
                ' ' . print_r($response['body']['message'] ?? '', true)
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
        try {
            $response = $this->getCommandBus()->handle(
                new \PrestaShop\Module\PsAccounts\Domain\Shop\Command\DeleteUserShopCommand(
                    $params['object']->id
                )
            );
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
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookActionAdminLoginControllerLoginAfter($params)
    {
        /** @var Employee $employee */
        $employee = $params['employee'];

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
            $analyticsService->group($uid, (string) $psAccountsService->getShopUuid());
            $analyticsService->trackUserSignedIntoBackOfficeLocally($uid, $email);
        }
    }

    /**
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function hookActionAdminControllerInitBefore($params)
    {
        /** @var \PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login $login */
        $login = $this->getService(\PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login::class);

        if (isset($_GET['logout'])) {
            if ($login->isEnabled()) {
                $this->oauth2Logout();
            } else {
                $this->getOauth2Session()->clear();
            }
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
        // login is supposed to be enabled when OauthClient is registered
        ///** @var \PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login $login */
        //$login = $this->getService(\PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login::class);
        //$login->enable();
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
        $this->getCommandBus()->handle(
            new \PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand()
        );
    }

    /**
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
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
     * Render the configuration form.
     *
     * @return string
     *
     * @throws PrestaShopException
<<<<<<< HEAD
     * @throws \PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException
     */
    public function getContent()
    {
        //$this->context->smarty->assign('pathVendor', $this->_path . 'views/js/chunk-vendors.' . $this->version . '.js');
=======
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
     */
    protected function loadAssets()
    {
        $this->context->smarty->assign('pathVendor', $this->_path . 'views/js/chunk-vendors.' . $this->version . '.js');
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
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

        return $this->display(__FILE__, 'views/templates/admin/app.tpl');
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
    public function getShopContext()
    {
        return $this->getService(\PrestaShop\Module\PsAccounts\Context\ShopContext::class);
    }

    /**
     * @return \PrestaShop\Module\PsAccounts\Middleware\Oauth2Middleware
     *
     * @throws Exception
     */
    public function getOauth2Middleware()
    {
        return $this->getService(\PrestaShop\Module\PsAccounts\Middleware\Oauth2Middleware::class);
    }

    /**
     * @return bool
     */
    public function isShopEdition()
    {
        return Module::isEnabled('smb_edition');
    }

    /**
     * @return \PrestaShop\Module\PsAccounts\Session\Session
     *
     * @throws Exception
     */
    public function getSession()
    {
        $container = $this->getCoreServiceContainer();
        if ($container) {
            try {
                /** @var \PrestaShop\Module\PsAccounts\Session\Session $session */
                $session = $container->get('session');
            } catch (\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException $e) {
                // FIXME: fix for 1.7.7.x
                global $kernel;
                $session = $kernel->getContainer()->get('session');
            }

            return $session;
        } else {
            // FIXME return a session like with configuration storage
            return new \PrestaShop\Module\PsAccounts\Session\FallbackSession(
                $this->getService(\PrestaShop\Module\PsAccounts\Adapter\Configuration::class)
            );
        }
    }

    /**
     * @deprecated
     *
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
     * @deprecated shouldn't exist anymore
     *
     * @return void
     *
     * @throws Exception
     */
    private function installEventBus()
    {
        if ($this->getShopContext()->isShop17()) {
            /** @var \PrestaShop\Module\PsAccounts\Installer\Installer $moduleInstaller */
            $moduleInstaller = $this->getService(\PrestaShop\Module\PsAccounts\Installer\Installer::class);

            // Ignore fail on ps_eventbus install
            $moduleInstaller->installModule('ps_eventbus');
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function onModuleReset()
    {
        /** @var \PrestaShop\Module\PsAccounts\Factory\CircuitBreakerFactory $circuitBreakerFactory */
        $circuitBreakerFactory = $this->getService(\PrestaShop\Module\PsAccounts\Factory\CircuitBreakerFactory::class);
        $circuitBreakerFactory->resetAll();

        /** @var \PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository $configurationRepository */
        $configurationRepository = $this->getService(\PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository::class);
        $configurationRepository->fixMultiShopConfig();

<<<<<<< HEAD
        // FIXME: this wont prevent from re-implanting override on reset of module
        $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($this, Db::getInstance());
        $uninstaller->deleteAdminTab('AdminLogin');

//        $this->installEventBus();
//        $this->autoReonboardOnV5();
    }
}

/**
 * @return void
 */
function ps_accounts_fix_upgrade()
{
    $root = __DIR__;
    $requires = array_merge([
        $root . '/src/Module/Install.php',
//        $root . '/src/Hook/Hook.php',
        $root . '/src/Hook/HookableTrait.php',
    ], []/*, glob($root . '/src/Hook/*.php')*/);

    foreach ($requires as $filename) {
        require_once $filename;
=======
    protected function getProvider(): PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopClientProvider
    {
        return $this->getService(\PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopClientProvider::class);
    }

    protected function isOauth2LogoutEnabled(): bool
    {
        return $this->hasParameter('ps_accounts.oauth2_url_session_logout');
    }

    protected function getOauth2Session(): PrestaShop\Module\PsAccounts\Domain\Account\Entity\AccountSession
    {
        return $this->getService(\PrestaShop\Module\PsAccounts\Domain\Account\Entity\AccountSession::class);
    }

    /**
     * @return \PrestaShop\Module\PsAccounts\Cqrs\CommandBus
     *
     * @throws Exception
     */
    private function getCommandBus(): PrestaShop\Module\PsAccounts\Cqrs\CommandBus
    {
        /** @var \PrestaShop\Module\PsAccounts\Cqrs\CommandBus $commandBus */
        $commandBus = $this->getService(
            \PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class
        );

        return $commandBus;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }
}
