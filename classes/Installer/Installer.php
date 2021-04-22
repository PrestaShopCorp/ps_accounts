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

namespace PrestaShop\Module\PsAccounts\Installer;

use Module;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Handler\Error\Sentry;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use Tools;

/**
 * Install ps_accounts module
 */
class Installer
{
    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var Link
     */
    private $link;

    /**
     * Install constructor.
     *
     * @param ShopContext $shopContext
     * @param Link $link
     */
    public function __construct(
        ShopContext $shopContext,
        Link $link
    ) {
        $this->shopContext = $shopContext;

        $this->link = $link;
    }

    /**
     * @param string $module
     * @param bool $upgrade
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function installModule($module, $upgrade = true)
    {
        if (false === $this->shopContext->isShop17()) {
            return true;
        }

        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        if (false === $upgrade && true === $moduleManager->isInstalled($module)) {
            return true;
        }

        // install or upgrade module
        $moduleIsInstalled = $moduleManager->install($module);

        if (false === $moduleIsInstalled) {
            Sentry::capture(new \Exception("Module ${module} can't be installed"));
        }

        return $moduleIsInstalled;
    }

    /**
     * @param string $module
     * @param string $psxName
     *
     * @return string | null
     *
     * @throws \PrestaShopException
     */
    public function getInstallUrl($module, $psxName)
    {
        if ($this->shopContext->isShop17()) {
            $router = SymfonyContainer::getInstance()->get('router');

            return Tools::getHttpHost(true) . $router->generate('admin_module_manage_action', [
                    'action' => 'install',
                    'module_name' => $module,
                ]);
        }

        return $this->link->getAdminLink('AdminModules', true, [], [
            'module_name' => $psxName,
            'configure' => $psxName,
            'install' => $module,
        ]);
    }

    /**
     * @param string $module
     * @param string $psxName
     *
     * @return string | null
     *
     * @throws \PrestaShopException
     */
    public function getEnableUrl($module, $psxName)
    {
        if ($this->shopContext->isShop17()) {
            $router = SymfonyContainer::getInstance()->get('router');

            return Tools::getHttpHost(true) . $router->generate('admin_module_manage_action', [
                    'action' => 'enable',
                    'module_name' => $module,
                ]);
        }

        return $this->link->getAdminLink('AdminModules', true, [], [
            'module_name' => $psxName,
            'configure' => $psxName,
            //'enable' => $module,
            'install' => $module,
        ]);
    }

    /**
     * @param string $module
     *
     * @return bool
     */
    public function isInstalled($module)
    {
        if (false === $this->shopContext->isShop17()) {
            return Module::isInstalled('ps_eventbus');
        }
        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        return $moduleManager->isInstalled($module);
    }

    /**
     * @param string $module
     *
     * @return bool
     */
    public function isEnabled($module)
    {
        if (false === $this->shopContext->isShop17()) {
            return Module::isEnabled($module);
        }
        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        return $moduleManager->isEnabled($module);
    }
}
