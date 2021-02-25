<?php

namespace PrestaShop\Module\PsAccounts\Configuration;

use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;

interface Configurable
{
    /**
     * @param array $config
     * @param array $defaults
     *
     * @return mixed
     *
     * @throws OptionResolutionException
     */
    public function resolveConfig(array $config, array $defaults = []);
}
