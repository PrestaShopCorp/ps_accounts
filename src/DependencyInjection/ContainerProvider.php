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

use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\ModuleLibCacheDirectoryProvider\Cache\CacheDirectoryProvider;
use PrestaShop\Module\PsAccounts\Vendor\Symfony\Component\Config\ConfigCache;
use PrestaShop\Module\PsAccounts\Vendor\Symfony\Component\Config\FileLocator;
use PrestaShop\Module\PsAccounts\Vendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PrestaShop\Module\PsAccounts\Vendor\Symfony\Component\DependencyInjection\ContainerInterface;
use PrestaShop\Module\PsAccounts\Vendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use PrestaShop\Module\PsAccounts\Vendor\Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContainerProvider
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
     * @var CacheDirectoryProvider
     */
    private $cacheDirectory;

    /**
     * @param string $moduleName
     * @param string $moduleLocalPath
     * @param string $moduleEnv
     * @param CacheDirectoryProvider $cacheDirectory
     */
    public function __construct(
        $moduleName,
        $moduleLocalPath,
        $moduleEnv,
        CacheDirectoryProvider $cacheDirectory
    ) {
        $this->moduleName = $moduleName;
        $this->moduleLocalPath = $moduleLocalPath;
        $this->moduleEnv = $moduleEnv;
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * @param string $containerName
     *
     * @return ContainerInterface
     *
     * @throws \Exception
     */
    public function get($containerName)
    {
        $containerClassName = ucfirst($this->moduleName)
            . ucfirst($containerName)
            . 'Container'
        ;
        $containerFilePath = $this->cacheDirectory->getPath() . '/' . $containerClassName . '.php';
        $containerConfigCache = new ConfigCache($containerFilePath, _PS_MODE_DEV_);

        if ($containerConfigCache->isFresh()) {
            require_once $containerFilePath;

            /** @var ContainerInterface $instance */
            $instance = new $containerClassName();

            return $instance;
        }

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->set(
            $this->moduleName . '.cache.directory',
            $this->cacheDirectory
        );
        $moduleConfigPath = $this->moduleLocalPath
            . 'config/'
            . $containerName
        ;

        $loader = new YamlFileLoader($containerBuilder, new FileLocator($moduleConfigPath));

        $loader->load('services' . ($this->moduleEnv ? '_' . $this->moduleEnv : '') . '.yml');

        $containerBuilder->compile();
        $dumper = new PhpDumper($containerBuilder);
        $containerConfigCache->write(
            $dumper->dump(['class' => $containerClassName]),
            $containerBuilder->getResources()
        );

        return $containerBuilder;
    }
}
