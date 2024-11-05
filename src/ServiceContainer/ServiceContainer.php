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


namespace PrestaShop\Module\PsAccounts\ServiceContainer;

class ServiceContainer
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $services = [];

    /**
     * @var array|\Closure[]
     */
    protected $providers = [];

    /**
     * @var string
     */
    protected $configName = 'config';

    /**
     * @var ServiceContainer
     */
    private static $instance;

    public function __construct()
    {
        $this->config = require_once __DIR__ . '/../../' . $this->configName . '.php';

        $this->providers = [
            'ps_accounts.context' => function () {
                return \Context::getContext();
            },
            'ps_accounts.logger' => function () {
                return \PrestaShop\Module\PsAccounts\Log\Logger::create();
            },
            'ps_accounts.module' => function () {
                return \Module::getInstanceByName('ps_accounts');
            },
        ];
    }

    /**
     * @return ServiceContainer
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new ServiceContainer();
        }

        return self::$instance;
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws ServiceNotFoundException
     */
    public function get($name)
    {
        if ($this->has($name)) {
            return $this->services[$name];
        }
        if (array_key_exists($name, $this->providers)) {
            return $this->providers[$name]();
        }

        return $this->provideInstanceFromClassname($name);
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws ServiceNotFoundException
     */
    public function getService($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    public function set($name, $value)
    {
        $this->services[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->services);
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws ParameterNotFoundException
     */
    public function getParameter($name)
    {
        if (array_key_exists($name, $this->config)) {
            return $this->config[$name];
        }
        throw new ParameterNotFoundException('Configuration parameter "' . $name . '" not found.');
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter($name)
    {
        return array_key_exists($name, $this->config);
    }

    /**
     * @param string $className
     *
     * @return mixed
     *
     * @throws ServiceNotFoundException
     */
    protected function provideInstanceFromClassname($className)
    {
        if (class_exists($className) && method_exists($className, 'getInstance')) {
            $this->set($className, $className::getInstance($this));

            return $this->services[$className];
        }
        throw new ServiceNotFoundException('Service Not Found: ' . $className);
    }
}
