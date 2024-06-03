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

    /**
     * @param string $resourceId
     * @param string $prefix
     * @param Configuration $config
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function __construct($resourceId, $prefix, Configuration $config)
    {
        parent::__construct($resourceId);

        $this->prefix = $prefix;
        $this->config = $config;

        // safeguard here
        if ($this->getLastFailureTime() > $this->getCurrentTimestamp()) {
            $this->reset();
        }
    }

    /**
     * @return int
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getFailureCount()
    {
        return (int) $this->get(self::FAILURE_COUNT);
    }

    /**
     * @param int $failureCount
     *
     * @return void
     */
    protected function setFailureCount($failureCount)
    {
        $this->set(self::FAILURE_COUNT, $failureCount);
    }

    /**
     * @return int|null
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    protected function getLastFailureTime()
    {
        return (int) $this->get(self::LAST_FAILURE_TIME);
    }

    /**
     * @param int|null $lastFailureTime
     *
     * @return void
     */
    protected function setLastFailureTime($lastFailureTime)
    {
        $this->set(self::LAST_FAILURE_TIME, $lastFailureTime);
    }

    /**
     * @param string $key
     *
     * @return string
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function get($key)
    {
        return (string) $this->config->getUncached($this->getKey($key), 0, 0, false);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    private function set($key, $value)
    {
        //$this->config->setRaw($this->getKey($key), $value, false, 0, 0);
        $this->config->setGlobal($this->getKey($key), $value);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getKey($key)
    {
        return $this->prefix . '_' . $this->resourceId . '_' . $key;
    }
}
