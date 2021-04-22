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

namespace PrestaShop\Module\PsAccounts\DependencyInjection;

use PrestaShop\ModuleLibCacheDirectoryProvider\Cache\CacheDirectoryProvider;
//use PrestaShop\ModuleLibServiceContainer\DependencyInjection\ContainerProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ServiceContainer
{
    /**
     * @var string Module Name
     */
    private $moduleName;

    /**
     * @var string Module Local Path
     */
    private $moduleLocalPath;

    /**
     * @var string
     */
    private $moduleEnv;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param string $moduleName
     * @param string $moduleLocalPath
     * @param string $moduleEnv
     *
     * @throws \Exception
     */
    public function __construct($moduleName, $moduleLocalPath, $moduleEnv)
    {
        $this->moduleName = $moduleName;
        $this->moduleLocalPath = $moduleLocalPath;
        $this->moduleEnv = $moduleEnv;

        $this->initContainer();
    }

    /**
     * @param string $serviceName
     *
     * @return object|null
     *
     * @throws \Exception
     */
    public function getService($serviceName)
    {
        if (null === $this->container) {
            $this->initContainer();
        }

        return $this->container->get($serviceName);
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Instantiate a new ContainerProvider
     *
     * @return void
     *
     * @throws \Exception
     */
    private function initContainer()
    {
        $cacheDirectory = new CacheDirectoryProvider(
            _PS_VERSION_,
            _PS_ROOT_DIR_,
            _PS_MODE_DEV_
        );
        $containerProvider = new ContainerProvider($this->moduleName, $this->moduleLocalPath, $this->moduleEnv, $cacheDirectory);

        $this->container = $containerProvider->get(defined('_PS_ADMIN_DIR_') || defined('PS_INSTALLATION_IN_PROGRESS') ? 'admin' : 'front');
    }
}
