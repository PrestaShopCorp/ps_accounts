<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;

class PersistentCircuitBreaker extends CircuitBreaker
{
    const FAILURE_COUNT = 'FAILURE_COUNT';
    const LAST_FAILURE_TIME = 'LAST_FAILURE_TIME';

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var Configuration
     */
    private $config;

    public function __construct(string $resourceId, string $prefix, Configuration $config)
    {
        parent::__construct($resourceId);

        $this->prefix = $prefix;
        $this->config = $config;

        // safeguard here
        if ($this->getLastFailureTime() > $this->getCurrentTimestamp()) {
            $this->reset();
        }
    }

    public function getFailureCount(): int
    {
        return (int) $this->get(self::FAILURE_COUNT);
    }

    protected function setFailureCount(int $failureCount): void
    {
        $this->set(self::FAILURE_COUNT, $failureCount);
    }

    protected function getLastFailureTime(): ?int
    {
        return (int) $this->get(self::LAST_FAILURE_TIME);
    }

    protected function setLastFailureTime(?int $lastFailureTime): void
    {
        $this->set(self::LAST_FAILURE_TIME, $lastFailureTime);
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function get(string $key): string
    {
        return (string) $this->config->getUncached($this->getKey($key), 0, 0, false);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    private function set(string $key, $value): void
    {
        //$this->config->setRaw($this->getKey($key), $value, false, 0, 0);
        $this->config->setGlobal($this->getKey($key), $value);
    }

    private function getKey(string $key): string
    {
        return $this->prefix . '_' . $this->resourceId . '_' . $key;
    }
}
