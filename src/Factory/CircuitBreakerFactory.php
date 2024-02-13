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
use PrestaShop\Module\PsAccounts\Api\Client\IndirectChannelClient;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\CircuitBreaker;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\PersistentCircuitBreaker;

class CircuitBreakerFactory
{
    /**
     * @var array
     */
    private static $provides = [
        AccountsClient::class,
        IndirectChannelClient::class,
    ];

    /**
     * @var array
     */
    private static $instances = [];

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
        /** @var Configuration $config */
        $config = $module->getService(Configuration::class);

        $instance = new PersistentCircuitBreaker($resourceId, 'PS_ACCOUNTS', $config);
        $instance->setDefaultFallbackResponse([
            'status' => false,
            'httpCode' => 500,
            'body' => ['message' => 'Circuit Breaker Open'],
        ]);
        static::$instances[$resourceId] = $instance;

        return $instance;
    }

    /**
     * @param $resourceId
     *
     * @return CircuitBreaker
     *
     * @throws \Exception
     */
    public static function getOrCreate($resourceId)
    {
        if (! array_key_exists($resourceId, static::$instances)) {
            static::create($resourceId);
        }
        return static::$instances[$resourceId];
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public static function resetAll()
    {
        foreach (static::$provides as $class) {
            static::getOrCreate($class)->reset();
        }
    }
}
