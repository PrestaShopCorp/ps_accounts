<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker;

class InMemoryCircuitBreaker extends CircuitBreaker
{
    /** @var int */
    private $failureCount;

    /** @var int|null */
    private $lastFailureTime;

    public function __construct(string $resourceId)
    {
        parent::__construct($resourceId);

        $this->reset();
    }

    /**
     * @return int
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * @param int $failureCount
     */
    public function setFailureCount(int $failureCount): void
    {
        $this->failureCount = $failureCount;
    }

    /**
     * @return int|null
     */
    public function getLastFailureTime(): ?int
    {
        return $this->lastFailureTime;
    }

    /**
     * @param int|null $lastFailureTime
     */
    public function setLastFailureTime(?int $lastFailureTime): void
    {
        $this->lastFailureTime = $lastFailureTime;
    }
}
