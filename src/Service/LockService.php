<?php

namespace PrestaShop\Module\PsAccounts\Service;

use DateTime;
use Monolog\Logger;
use PrestaShop\Module\PsAccounts\Adapter\Configuration;

class LockService
{
//    const ON_TIMEOUT_ACQUIRE_LOCK = 1;
//    const  ON_TIMEOUT_THROW_EXCEPTION = 2;

    /** @var Configuration */
    private $config;

    /** @var Logger */
    private $logger;

    public function __construct(Configuration $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @throws \Throwable
     */
    public function callLocked($callback, $resourceId, $timeoutMs = 10000, $pollDelayMs = 50)
    {
        $this->acquireLock($resourceId, $timeoutMs, $pollDelayMs);
        try {
            $result = $callback();
            $this->releaseLock($resourceId);
            return $result;
        } catch (\Throwable $e) {
            $this->releaseLock($resourceId);
            throw $e;
        }
    }

    public function acquireLock($resourceId, $timeoutMs = 10000, $pollDelayMs = 50)
    {
        // FIXME: poll timeout
        // FIXME: implement two behaviours
        // ON_TIMEOUT_ACQUIRE_LOCK
        // ON_TIMEOUT_THROW_EXCEPTION
        $this->logger->debug('Trying to acquire lock [' .$resourceId . '] for ' . $pollDelayMs . 'ms');
        while(($timestamp = (int) $this->readLock($resourceId)) > 0 /*&&
            (new DateTime())->format('Uv') - $timestamp < $timeoutMs*/) {
            $this->logger->debug('Waiting to acquire lock [' .$resourceId . '] for ' . $pollDelayMs . 'ms (' . $timestamp . ')');
            usleep($pollDelayMs * 1000);
        }
        $this->writeLock($resourceId, (new \DateTime())->format('Uv'));
        $this->logger->debug('Lock acquired [' .$resourceId . '] for ' . $pollDelayMs . 'ms');
    }

    private function releaseLock($resourceId)
    {
        $this->writeLock($resourceId, '0');
    }

    protected function writeLock($resourceId, $value)
    {
        $this->config->setRaw($resourceId, $value);
    }

    protected function readLock($resourceId)
    {
        $lock =  $this->config->getUncached($resourceId);
        $this->logger->debug('Reading lock : ' . $resourceId . '|' . $lock);
        return $lock;
    }
}
