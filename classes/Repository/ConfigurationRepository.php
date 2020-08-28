<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Configuration;

class ConfigurationRepository
{
    /**
     * We wrap Configuration::get function in here to be able to mock static functions
     *
     * @param string $key
     *
     * @return bool|string
     */
    public function get($key)
    {
        return Configuration::get($key);
    }
}
