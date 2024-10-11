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

namespace PrestaShop\Module\PsAccounts\Factory;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\CircuitBreaker;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\PersistentCircuitBreaker;

class CircuitBreakerFactory
{
    /**
     * @var array
     */
    private $provides = [
        AccountsClient::class,
    ];

    /**
     * @var array
     */
    private $instances = [];

    /**
     * @var Configuration
     */
    private $configStorage;

    /**
     * @param Configuration $configStorage
     */
    public function __construct(
        Configuration $configStorage
    ) {
        $this->configStorage = $configStorage;
    }

    /**
     * @param string $resourceId
     *
     * @return CircuitBreaker
     *
     * @throws \Exception
     */
    public function createInstance($resourceId)
    {
        $instance = new PersistentCircuitBreaker(
            static::className($resourceId),
            'PS_ACCOUNTS',
            $this->configStorage
        );
        $instance->setDefaultFallbackResponse([
            'status' => false,
            'httpCode' => 500,
            'body' => ['message' => 'Circuit Breaker Open'],
        ]);
        $this->instances[$resourceId] = $instance;

        return $instance;
    }

    /**
     * @param string $resourceId
     *
     * @return CircuitBreaker
     *
     * @throws \Exception
     */
    public function getOrCreate($resourceId)
    {
        if (!array_key_exists($resourceId, $this->instances)) {
            static::createInstance($resourceId);
        }

        return $this->instances[$resourceId];
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function resetAll()
    {
        foreach ($this->provides as $class) {
            static::getOrCreate($class)->reset();
        }
    }

    /**
     * @param string $resourceId
     *
     * @return CircuitBreaker
     *
     * @throws \Exception
     */
    public static function create($resourceId)
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var CircuitBreakerFactory $factory */
        $factory = $module->getService(CircuitBreakerFactory::class);

        return $factory->createInstance($resourceId);
    }

    /**
     * @param string $className
     *
     * @return string
     */
    protected function className($className)
    {
        return strtoupper(preg_replace(['/^.*\\\\/', '/([^A-Z])([A-Z])/'], ['', '$1_$2'], $className));
    }
}
