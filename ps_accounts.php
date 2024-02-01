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
    use \PrestaShop\Module\PsAccounts\Hook\HookableTrait;

    const DEFAULT_ENV = '';

    // Needed in order to retrieve the module version easier (in api call headers) than instanciate
    // the module each time to get the version
    const VERSION = '6.4.0';

    /**
     * Admin tabs
     *
     * @var array
     */
    private $adminControllers = [
        AdminAjaxPsAccountsController::class,
        AdminDebugPsAccountsController::class,
        AdminOAuth2PsAccountsController::class,
        AdminLoginPsAccountsController::class,
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
    ];

    /**
     * Hooks to register
     *
     * @var array
     */
    private $hooks = [
//        \PrestaShop\Module\PsAccounts\Hook\ActionAdminControllerInitBefore::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionAdminLoginControllerLoginAfter::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionAdminLoginControllerSetMedia::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionModuleInstallAfter::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionObjectShopAddAfter::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionObjectShopDeleteAfter::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionObjectShopDeleteBefore::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionObjectShopUpdateAfter::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionObjectShopUrlUpdateAfter::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionShopAccountLinkAfter::class,
        \PrestaShop\Module\PsAccounts\Hook\ActionShopAccountUnlinkAfter::class,
        \PrestaShop\Module\PsAccounts\Hook\DisplayAccountUpdateWarning::class,
        \PrestaShop\Module\PsAccounts\Hook\DisplayBackOfficeHeader::class,
        \PrestaShop\Module\PsAccounts\Hook\DisplayBackOfficeEmployeeMenu::class,
        \PrestaShop\Module\PsAccounts\Hook\DisplayDashboardTop::class,
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
        $this->version = '6.4.0';

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
     * @throws Exception
     */
    public function install()
    {
        $installer = new PrestaShop\Module\PsAccounts\Module\Install($this, Db::getInstance());

        $status = $installer->installInMenu()
            && $installer->installDatabaseTables()
            && parent::install()
            && $this->addCustomHooks($this->customHooks)
            && $this->registerHook($this->getHooksToRegister());

        $this->installEventBus();
        $this->fixMultiShopConfig();
        $this->autoReonboardOnV5();

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
     * @return array
     */
    public function getAdminControllers()
    {
        return array_map(function ($className) {
            return preg_replace('/^.*?(\w+)Controller$/', '\1', $className);
        }, $this->adminControllers);
    }

    /**
     * @return array
     */
    public function getHooksToRegister()
    {
        return array_map(function ($className) {
            return $className::getName();
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
     * @throws \PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException
     */
    public function getContent()
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
     * @return void
     *
     * @throws Exception
     */
    public function fixMultiShopConfig()
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
     * @return bool
     */
    public function isShopEdition()
    {
        return Module::isEnabled('smb_edition');
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
    public function resetCircuitBreaker()
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
}
