<?php

namespace PrestaShop\Module\PsAccounts\Http\Client;

class ConnectException extends ClientException
{
    public function isBreaking()
    {
        return true;
    }
}
