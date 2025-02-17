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

namespace PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker;

use DateTime;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Vendor\GuzzleHttp\Exception\ConnectException;
use PrestaShop\Module\PsAccounts\Vendor\GuzzleHttp\Exception\RequestException;

abstract class CircuitBreaker
{
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
        if ($this->state() !== State::OPEN) {
            try {
                $result = $callback();
                $this->reset();

                return $result;
            } catch (ConnectException $e) {
                // FIXME: CircuitBreak bound to GuzzleException
                $this->setLastFailure();
                Logger::getInstance()->error($e->getMessage());
            } catch (RequestException $e) {
                Logger::getInstance()->error($e->getMessage());
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
            return State::HALF_OPEN;
        } elseif ($this->getFailureCount() >= $this->threshold) {
            return State::OPEN;
        } else {
            return State::CLOSED;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) \json_encode([
            'state' => $this->state(),
            'threshold' => $this->getThreshold(),
            'reset_timeout_ms' => $this->getResetTimeoutMs(),
            'last_failure_time' => $this->getLastFailureTime(),
            'current_timestamp' => $this->getCurrentTimestamp(),
            'diff' => ($this->getCurrentTimestamp() - $this->getLastFailureTime()),
            'failure_count' => $this->getFailureCount(),
        ], JSON_PRETTY_PRINT);
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
        //return (int) (new DateTime())->format('Uv');
        return (int) floor(microtime(true) * 1000);
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
