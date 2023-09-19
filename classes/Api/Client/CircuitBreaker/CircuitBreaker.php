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

    /**
     * @param string $resourceId
     */
    public function __construct($resourceId)
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

        return isset($fallbackResponse) ? $fallbackResponse : $this->defaultFallbackResponse;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->setFailureCount(0);
        $this->setLastFailureTime(null);
    }

    /**
     * @param mixed $defaultFallbackResponse
     *
     * @return void
     */
    public function setDefaultFallbackResponse($defaultFallbackResponse)
    {
        $this->defaultFallbackResponse = $defaultFallbackResponse;
    }

    /**
     * @return int
     */
    public function getResetTimeoutMs()
    {
        return $this->resetTimeoutMs;
    }

    /**
     * @param int $resetTimeoutMs
     *
     * @return void
     */
    public function setResetTimeoutMs($resetTimeoutMs)
    {
        $this->resetTimeoutMs = $resetTimeoutMs;
    }

    /**
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @param int $threshold
     *
     * @return void
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;
    }

    /**
     * @return int
     */
    public function state()
    {
        if ($this->getFailureCount() >= $this->threshold &&
            $this->getCurrentTimestamp() - $this->getLastFailureTime() >= $this->resetTimeoutMs) {
            return self::CIRCUIT_BREAKER_STATE_HALF_OPEN;
        } elseif ($this->getFailureCount() >= $this->threshold) {
            return self::CIRCUIT_BREAKER_STATE_OPEN;
        } else {
            return self::CIRCUIT_BREAKER_STATE_CLOSED;
        }
    }

    /**
     * @return void
     */
    protected function setLastFailure()
    {
        $this->setLastFailureTime($this->getCurrentTimestamp());
        $this->setFailureCount($this->getFailureCount() + 1);
    }

    /**
     * @return int
     */
    protected function getCurrentTimestamp()
    {
        return (int) (new DateTime())->format('Uv');
    }

    /**
     * @return int
     */
    abstract protected function getFailureCount();

    /**
     * @param int $failureCount
     *
     * @return void
     */
    abstract protected function setFailureCount($failureCount);

    /**
     * @return int|null
     */
    abstract protected function getLastFailureTime();

    /**
     * @param int|null $lastFailureTime
     *
     * @return void
     */
    abstract protected function setLastFailureTime($lastFailureTime);
}
