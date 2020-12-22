<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Installer;

use Module;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManager;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use Symfony\Component\Routing\Router;
use Tools;

/**
 * Install ps_accounts module
 */
class Installer
{
    const PS_ACCOUNTS = 'ps_accounts';
    const PS_EVENTBUS = 'ps_eventbus';

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    /**
     * @var Link
     */
    private $link;

    /**
     * @var Router
     */
    private $router;

    /**
     * Install constructor.
     *
     * @param ShopContext $shopContext
     * @param Link $link
     * @param ErrorHandler $errorHandler
     */
    public function __construct(
        ShopContext $shopContext,
        Link $link,
        ErrorHandler $errorHandler
    ) {
        $this->shopContext = $shopContext;

        $this->link = $link;

        //$this->router = $router;

        $this->errorHandler = $errorHandler;

        $this->moduleManager = ModuleManagerBuilder::getInstance()->build();
    }

    /**
     * @param $moduleName
     * @param bool $upgrade
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function installModule($moduleName, $upgrade = true)
    {
        if (true === $this->shopContext->isShop17()) {
            return true;
        }

        if (false === $upgrade && true === $this->moduleManager->isInstalled($moduleName)) {
            return true;
        }

        // install or upgrade module
        $moduleIsInstalled = $this->moduleManager->install($moduleName);

        if (false === $moduleIsInstalled) {
            throw new \Exception("Module ${moduleName} can't be installed", 500);
        }

        return $moduleIsInstalled;
    }

    /**
     * @param bool $upgrade
     * @return bool
     *
     * @throws \Exception
     */
    public function installDependencies($upgrade = true)
    {
        return $this->installModule(self::PS_ACCOUNTS, $upgrade)
            && $this->installModule(self::PS_EVENTBUS, $upgrade);

    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function installPsAccounts()
    {
        try {
            return $this->installModule(self::PS_ACCOUNTS, false);
        } catch (\Exception $e) {
            $this->errorHandler->handle($e, 500);
            return true;
        }
    }

    /**
     * @param $psxName
     *
     * @return string | null
     *
     * @throws \PrestaShopException
     */
    public function getPsAccountsInstallLink($psxName)
    {
        // FIXME : wrong responsibility here
        if (true === Module::isInstalled('ps_accounts')) {
            return null;
        }

        if ($this->shopContext->isShop17()) {
            $router = SymfonyContainer::getInstance()->get('router');
            return Tools::getHttpHost(true) . $router->generate('admin_module_manage_action', [
                    'action' => 'install',
                    'module_name' => 'ps_accounts',
                ]);
        }

        return  $this->link->getAdminLink('AdminModules', true, [], [
            'module_name' => $psxName,
            'configure' => $psxName,
            'install' => 'ps_accounts',
        ]);
    }

    /**
     * @param $psxName
     *
     * @return string | null
     *
     * @throws \PrestaShopException
     */
    public function getPsAccountsEnableLink($psxName)
    {
        // FIXME : wrong responsibility here
        if (true === Module::isEnabled('ps_accounts')) {
            return null;
        }

        if ($this->shopContext->isShop17()) {
            $router = SymfonyContainer::getInstance()->get('router');
            return Tools::getHttpHost(true) . $router->generate('admin_module_manage_action', [
                    'action' => 'enable',
                    'module_name' => 'ps_accounts',
                ]);
        }

        return  $this->link->getAdminLink('AdminModules', true, [], [
            'module_name' => $psxName,
            'configure' => $psxName,
            'enable' => 'ps_accounts',
        ]);
    }
}
