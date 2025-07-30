<?php

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class UpgradeService
{
    /**
     * @var ConfigurationRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $moduleName = 'ps_accounts';

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
    public function getRegisteredVersion()
    {
        return $this->repository->getLastUpgrade(false);
    }

    /**
     * @param string $version
     *
     * @return void
     */
    public function setRegisteredVersion($version = \Ps_accounts::VERSION)
    {
        $this->repository->updateLastUpgrade($version);
    }

    /**
     * @return string
     */
    public function getCoreRegisteredVersion()
    {
        return \Db::getInstance()->getValue(
            'SELECT version FROM ' . _DB_PREFIX_ . 'module WHERE name = \'' . $this->moduleName . '\''
        ) ?: '0';
    }

    /**
     * @param string $version
     *
     * @return void
     */
    public function setCoreRegisteredVersion($version = \Ps_accounts::VERSION)
    {
        \Db::getInstance()->execute(
            'UPDATE ' . _DB_PREFIX_ . 'module SET version = \'' . $version . '\' WHERE name = \'' . $this->moduleName . '\''
        );
    }
}
