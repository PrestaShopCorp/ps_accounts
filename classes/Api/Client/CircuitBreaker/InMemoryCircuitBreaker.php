<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker;

class InMemoryCircuitBreaker extends CircuitBreaker
{
    /** @var int */
    private $failureCount;

    /** @var int|null */
    private $lastFailureTime;

    /**
     * @param string $resourceId
     */
    public function __construct($resourceId)
    {
        parent::__construct($resourceId);

        $this->reset();
    }

    /**
     * @return int
     */
    public function getFailureCount()
    {
        return $this->failureCount;
    }

    /**
     * @param int $failureCount
     */
    public function setFailureCount($failureCount)
    {
        $this->failureCount = $failureCount;
    }

    /**
     * @return int|null
     */
    public function getLastFailureTime()
    {
        return $this->lastFailureTime;
    }

    /**
     * @param int|null $lastFailureTime
     */
    public function setLastFailureTime($lastFailureTime)
    {
        $this->lastFailureTime = $lastFailureTime;
    }
}
