<?php

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class UpgradeService
{
    const MODULE_NAME = 'ps_accounts';

    /**
     * @var ConfigurationRepository
     */
    private $repository;

    /**
     * @param ConfigurationRepository $configurationRepository
     */
    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->repository = $configurationRepository;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $version = $this->getRegisteredVersion();

        if ($version === '0') {
            $version = $this->getCoreRegisteredVersion();
        }

        return $version;
    }

    /**
     * @param string $version
     *
     * @return void
     */
    public function setVersion($version = \Ps_accounts::VERSION)
    {
        $this->repository->updateLastUpgrade($version);
    }

    /**
     * @return string
     */
    public function getRegisteredVersion()
    {
        return $this->repository->getLastUpgrade(false);
    }

    /**
     * @return string
     */
    public function getCoreRegisteredVersion()
    {
        return \Db::getInstance()->getValue(
            'SELECT version FROM ' . _DB_PREFIX_ . 'module WHERE name = \'' . self::MODULE_NAME . '\''
        ) ?: '0';
    }
}
