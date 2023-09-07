<?php

namespace PrestaShop\Module\PsAccounts\Api\Client;

use DateTime;
use GuzzleHttp\Exception\ConnectException;

class CircuitBreaker
{
    const CIRCUIT_BREAKER_STATE_OPEN = 0;
    const CIRCUIT_BREAKER_STATE_CLOSED = 1;
    const CIRCUIT_BREAKER_STATE_HALF_OPEN = 2;

    /** @var int  */
    private $resetTimeoutMs = 5000;

    /** @var int */
    private $threshold = 2;

    /** @var int */
    private $failureCount;

    /** @var DateTime */
    private $lastFailureTime;

    public function construct()
    {
        $this->reset();
    }

    public function call($callback, $fallbackResponse)
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
        return $fallbackResponse;
    }

    protected function setLastFailure()
    {
        $this->lastFailureTime = (new DateTime())->format('Uv');
        $this->failureCount++;
    }

    protected function state(): int
    {
        if ($this->failureCount >= $this->threshold &&
            (new DateTime())->format('Uv') - $this->lastFailureTime >= $this->resetTimeoutMs) {
            return self::CIRCUIT_BREAKER_STATE_HALF_OPEN;
        } else if ($this->failureCount >= $this->threshold) {
            return self::CIRCUIT_BREAKER_STATE_OPEN;
        } else {
            return self::CIRCUIT_BREAKER_STATE_CLOSED;
        }
    }

    protected function reset()
    {
        $this->failureCount = 0;
        $this->lastFailureTime = null;
    }
}
