<?php

namespace PrestaShop\Module\PsAccounts\Api\Client;

use DateTime;
use GuzzleHttp\Exception\ConnectException;

class CircuitBreaker
{
    const CIRCUIT_BREAKER_STATE_OPEN = 0;
    const CIRCUIT_BREAKER_STATE_CLOSED = 1;

    /** @var DateTime */
    private $lastFailureTime;

    /** @var int  */
    private $resetTimeoutMs = 5000;

    public function getState(): int
    {
        if ($this->lastFailureTime) {
            $elapsedTimeMs = (new DateTime())->format('Uv') - $this->lastFailureTime;
            if ($elapsedTimeMs < $this->resetTimeoutMs) {
                return self::CIRCUIT_BREAKER_STATE_OPEN;
            }
            $this->lastFailureTime = null;
        }
        return self::CIRCUIT_BREAKER_STATE_CLOSED;
    }

    public function call($callback, $fallbackResponse)
    {
        if ($this->getState() === self::CIRCUIT_BREAKER_STATE_CLOSED) {
            try {
                return $callback();
            } catch (ConnectException $e) {
                $this->setLastFailure();
            }
        }
        return $fallbackResponse;
    }

    protected function setLastFailure()
    {
        $this->lastFailureTime = (new DateTime())->format('Uv');
    }
}
