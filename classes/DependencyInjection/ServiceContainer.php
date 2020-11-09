<?php

namespace PrestaShop\Module\PsAccounts\DependencyInjection;

use PrestaShop\ModuleLibCacheDirectoryProvider\Cache\CacheDirectoryProvider;
#use PrestaShop\ModuleLibServiceContainer\DependencyInjection\ContainerProvider;
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
     * @var ContainerInterface
     */
    public $container;

    /**
     * @param string $moduleName
     * @param string $moduleLocalPath
     */
    public function __construct($moduleName, $moduleLocalPath)
    {
        $this->moduleName = $moduleName;
        $this->moduleLocalPath = $moduleLocalPath;

        $this->initContainer();
    }

    /**
     * @param string $serviceName
     *
     * @return object|null
     */
    public function getService($serviceName)
    {
        if (null === $this->container) {
            $this->initContainer();
        }

        return $this->container->get($serviceName);
    }

    /**
     * Instantiate a new ContainerProvider
     */
    private function initContainer()
    {
        $cacheDirectory = new CacheDirectoryProvider(
            _PS_VERSION_,
            _PS_ROOT_DIR_,
            _PS_MODE_DEV_
        );
        $containerProvider = new ContainerProvider($this->moduleName, $this->moduleLocalPath, $cacheDirectory);

        $this->container = $containerProvider->get(defined('_PS_ADMIN_DIR_') || defined('PS_INSTALLATION_IN_PROGRESS') ? 'admin' : 'front');
    }
}
