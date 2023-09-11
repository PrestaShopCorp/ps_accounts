<?php

namespace PrestaShop\Module\PsAccounts\Service;

use DateTime;
use PrestaShop\Module\PsAccounts\Adapter\Configuration;

class LockService
{
//    const ON_TIMEOUT_ACQUIRE_LOCK = 1;
//    const  ON_TIMEOUT_THROW_EXCEPTION = 2;

    /** @var Configuration */
    private $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
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
        while(($timestamp =  $this->readLock($resourceId)) &&
            (new DateTime())->format('Uv') - $timestamp < $timeoutMs) {
            usleep($pollDelayMs * 1000);
        }
        $this->writeLock($resourceId, (new \DateTime())->format('Uv'));
    }

    private function releaseLock($resourceId)
    {
        $this->writeLock($resourceId, '0');
    }

    protected function writeLock($resourceId, $value)
    {
        //$this->config->setRaw($resourceId, '0', null, null, null);
        $this->config->set($resourceId, '0');
    }

    protected function readLock($resourceId)
    {
        //return $this->config->getRaw($resourceId, null, null);
        return $this->config->get($resourceId);
    }
}
