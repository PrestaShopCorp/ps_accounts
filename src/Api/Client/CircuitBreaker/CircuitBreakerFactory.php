<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;

class CircuitBreakerFactory
{
    public static function create($resourceId): CircuitBreaker
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');
        /** @var Configuration $config */
        $config = $module->getService(Configuration::class);

        $instance = new InMemoryCircuitBreaker($resourceId, 'PS_ACCOUNTS', $config);
        $instance->setDefaultFallbackResponse([
            'status' => false,
            'httpCode' => 500,
            'body' => ['message' => 'Circuit Breaker Timeout'],

        ]);
        return $instance;
    }
}
