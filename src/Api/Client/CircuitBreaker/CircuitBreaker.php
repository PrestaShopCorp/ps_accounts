<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker;

use DateTime;
use GuzzleHttp\Exception\ConnectException;

abstract class CircuitBreaker
{
    const CIRCUIT_BREAKER_STATE_OPEN = 0;
    const CIRCUIT_BREAKER_STATE_CLOSED = 1;
    const CIRCUIT_BREAKER_STATE_HALF_OPEN = 2;

    /** @var int */
    protected $resetTimeoutMs = 30000;

    /** @var int */
    protected $threshold = 2;

    /** @var string */
    protected $resourceId;

    /** @var mixed */
    protected $defaultFallbackResponse;

    public function __construct(string $resourceId)
    {
        $this->resourceId = $resourceId;
    }

    /**
     * @param mixed $callback
     * @param mixed $fallbackResponse
     *
     * @return mixed
     */
    public function call($callback, $fallbackResponse = null)
    {
        if ($this->state() !== self::CIRCUIT_BREAKER_STATE_OPEN) {
            try {
                $result = $callback();
                $this->reset();

                return $result;
            } catch (ConnectException $e) {
                $this->setLastFailure();
            }
        }

        return $fallbackResponse ?? $this->defaultFallbackResponse;
    }

    public function reset(): void
    {
        $this->setFailureCount(0);
        $this->setLastFailureTime(null);
    }

    /**
     * @param mixed $defaultFallbackResponse
     */
    public function setDefaultFallbackResponse($defaultFallbackResponse): void
    {
        $this->defaultFallbackResponse = $defaultFallbackResponse;
    }

    /**
     * @param int $resetTimeoutMs
     */
    public function setResetTimeoutMs(int $resetTimeoutMs): void
    {
        $this->resetTimeoutMs = $resetTimeoutMs;
    }

    /**
     * @param int $threshold
     */
    public function setThreshold(int $threshold): void
    {
        $this->threshold = $threshold;
    }

    protected function setLastFailure(): void
    {
        $this->setLastFailureTime((int) (new DateTime())->format('Uv'));
        $this->setFailureCount($this->getFailureCount() + 1);
    }

    /**
     * @return int
     */
    protected function state(): int
    {
        if ($this->getFailureCount() >= $this->threshold &&
            (int) (new DateTime())->format('Uv') - $this->getLastFailureTime() >= $this->resetTimeoutMs) {
            return self::CIRCUIT_BREAKER_STATE_HALF_OPEN;
        } elseif ($this->getFailureCount() >= $this->threshold) {
            return self::CIRCUIT_BREAKER_STATE_OPEN;
        } else {
            return self::CIRCUIT_BREAKER_STATE_CLOSED;
        }
    }

    /**
     * @return int
     */
    abstract protected function getFailureCount(): int;

    /**
     * @param int $failureCount
     */
    abstract protected function setFailureCount(int $failureCount): void;

    /**
     * @return int|null
     */
    abstract protected function getLastFailureTime(): ?int;

    /**
     * @param int|null $lastFailureTime
     */
    abstract protected function setLastFailureTime(?int $lastFailureTime): void;
}
