<?php

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
