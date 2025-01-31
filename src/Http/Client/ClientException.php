<?php

namespace PrestaShop\Module\PsAccounts\Http\Client;

use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\CircuitBreakerException;

class ClientException extends \Exception implements CircuitBreakerException
{
    public function isBreaking()
    {
        return false;
    }
}
