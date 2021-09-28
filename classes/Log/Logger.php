<?php

namespace PrestaShop\Module\PsAccounts\Log;

use Ps_accounts;

class Logger
{
    /**
     * @return \Monolog\Logger
     *
     * @throws \Exception
     */
    public static function getInstance()
    {
        /** @var Ps_accounts $psAccounts */
        $psAccounts = \Module::getInstanceByName('ps_accounts');

        return $psAccounts->getLogger();
    }
}
