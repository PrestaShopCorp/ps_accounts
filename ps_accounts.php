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

    // Needed in order to retrieve the module version easier (in api call headers) than instanciate
    // the module each time to get the version
    const VERSION = '8.0.10';

    /**
     * Admin tabs
     *
     * @var array class names
     */
    private $adminControllers = [
        'AdminAjaxPsAccountsController',
        'AdminAjaxV2PsAccountsController',
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
        'actionAdminLoginControllerSetMedia',
        //'actionAdminControllerSetMedia',
        'displayBackOfficeHeader',
        'actionObjectEmployeeDeleteAfter',
        'actionObjectShopAddAfter',
        'actionObjectShopDeleteAfter',
        'actionShopAccessTokenRefreshAfter',
        'displayBackOfficeEmployeeMenu',
    ];

    /**
     * @var \PrestaShop\Module\PsAccounts\ServiceContainer\PsAccountsContainer
     */
    private $moduleContainer;

    /**
     * Ps_accounts constructor.
     */
    public function __construct()
    {
        $this->name = 'ps_accounts';
        $this->tab = 'administration';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap = true;

        // We cannot use the const VERSION because the const is not computed by addons marketplace
        // when the zip is uploaded
        $this->version = '8.0.10';

        $this->module_key = 'abf2cd758b4d629b2944d3922ef9db73';

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Account');
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
     * @return \PrestaShop\Module\PsAccounts\Vendor\Monolog\Logger
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

        // FIXME: implement safe "reset" method
        $this->onModuleReset();

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
     * @phpstan-ignore-next-line
     *
     * @return \PrestaShop\PrestaShop\Adapter\SymfonyContainer|\Symfony\Component\DependencyInjection\ContainerInterface|null
     */
    public function getCoreServiceContainer()
    {
        /* @phpstan-ignore-next-line */
        if (method_exists($this, 'getContainer')) {
            return $this->getContainer();
        }

        if (class_exists('\PrestaShop\PrestaShop\Adapter\SymfonyContainer')) {
            return \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        }

        return null;
    }

    /**
     * @return \PrestaShop\Module\PsAccounts\ServiceContainer\PsAccountsContainer
     *
     * @throws Exception
     */
    public function getServiceContainer()
    {
        if (null === $this->moduleContainer) {
            $this->moduleContainer = (new \PrestaShop\Module\PsAccounts\ServiceContainer\PsAccountsContainer(
                __DIR__ . '/config.php'
            ))->init();
        }

        return $this->moduleContainer;
    }

    /**
     * @param string $serviceName
     *
     * @return mixed
     */
    public function getService($serviceName)
    {
        return $this->getServiceContainer()->getService($serviceName);
    }

    /**
     * @param string $serviceName
     *
     * @return bool
     */
    public function hasService($serviceName)
    {
        return $this->getServiceContainer()->has($serviceName);
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        return $this->getServiceContainer()->getParameter($name, $default);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter($name)
    {
        return $this->getServiceContainer()->hasParameter($name);
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
     */
    public function addCustomHooks($customHooks)
    {
        $ret = true;
        foreach ($customHooks as $customHook) {
            try {
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
            } catch (\Throwable $e) {
                /* @phpstan-ignore-next-line */
            } catch (\Exception $e) {
            }
        }

        return $ret;
    }

    /**
     * Render the configuration form.
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getContent()
    {
        if (!empty($settingsForm = (new \PrestaShop\Module\PsAccounts\Settings\SettingsForm($this))->render())) {
            return $settingsForm;
        }

        /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $psAccountsService */
        $psAccountsService = $this->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);

        //$this->context->smarty->assign('pathVendor', $this->_path . 'views/js/chunk-vendors.' . $this->version . '.js');
        $this->context->smarty->assign('urlAccountsCdn', $this->getParameter('ps_accounts.accounts_cdn_url'));
        $this->context->smarty->assign('componentInitParams', $psAccountsService->getComponentInitParams());

        return $this->display(__FILE__, 'views/templates/admin/app.tpl');
    }

    /**
     * @param array $params
     *
     * @return void
     */
    public function redirectSettingsPage(array $params = [])
    {
        Tools::redirectAdmin($this->getSettingsPageUrl($params));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getSettingsPageUrl(array $params = [])
    {
        if (version_compare(_PS_VERSION_, '1.7', '>')) {
            return $this->context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                array_merge($params, [
                    'configure' => $this->name,
                ])
            );
        } else {
            return AdminController::$currentIndex . '&' . http_build_query(array_merge($params, [
                'configure' => $this->name,
                'token' => Tools::getAdminTokenLite('AdminModules'),
            ]));
        }
    }

    /**
     * @return string
     */
    public function getAccountsUiUrl()
    {
        return $this->getParameter('ps_accounts.accounts_ui_url');
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
     * @return \PrestaShop\Module\PsAccounts\Context\ShopContext
     */
    public function getShopContext()
    {
        return $this->getService(\PrestaShop\Module\PsAccounts\Context\ShopContext::class);
    }

    /**
     * @return bool
     */
    public function isShopEdition()
    {
        return Module::isEnabled('smb_edition');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\SessionInterface
     *
     * @throws Exception
     */
    public function getSession()
    {
        // Class name must be literal here in case interface is not present (PrestaShop 1.6)
        return $this->getService('\Symfony\Component\HttpFoundation\Session\SessionInterface');
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function onModuleReset()
    {
        /** @var \PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository $configurationRepository */
        $configurationRepository = $this->getService(\PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository::class);
        $configurationRepository->fixMultiShopConfig(true);

        /** @var \PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\Factory $circuitBreakerFactory */
        $circuitBreakerFactory = $this->getService(\PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\Factory::class);
        $circuitBreakerFactory->resetAll();

        /** @var \PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service $oAuth2Service */
        $oAuth2Service = $this->getService(\PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service::class);
        $oAuth2Service->clearCache();

        // FIXME: this wont prevent from re-implanting override on reset of module
        $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($this, Db::getInstance());
        $uninstaller->deleteAdminTab('AdminLogin');

        /** @var \PrestaShop\Module\PsAccounts\Cqrs\CommandBus $commandBus */
        $commandBus = $this->getService(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);

        // Verification flow
        $commandBus->handle(new \PrestaShop\Module\PsAccounts\Account\Command\MigrateOrCreateIdentitiesV8Command());
    }

    /**
     * @return string
     */
    public function getCloudShopId()
    {
        /** @var \PrestaShop\Module\PsAccounts\Account\StatusManager $statusManager */
        $statusManager = $this->getService(\PrestaShop\Module\PsAccounts\Account\StatusManager::class);

        return $statusManager->getCloudShopId();
    }

    /**
     * @param string $source
     *
     * @return bool
     */
    public function getVerifiedStatus($source = 'ps_accounts')
    {
        /** @var \PrestaShop\Module\PsAccounts\Account\StatusManager $statusManager */
        $statusManager = $this->getService(\PrestaShop\Module\PsAccounts\Account\StatusManager::class);

        try {
            if ($statusManager->withSource($source)->getStatus()->isVerified) {
                return true;
            }
        } catch (\PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException $e) {
        }

        return false;
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
        //$root . '/src/Hook/Hook.php',
        $root . '/src/Hook/HookableTrait.php',
        $root . '/src/Settings/SettingsForm.php',
    ], []/*, glob($root . '/src/Hook/*.php')*/);

    foreach ($requires as $filename) {
        require_once $filename;
    }
}
