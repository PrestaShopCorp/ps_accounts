<?php

namespace PrestaShop\Module\PsAccounts\Http\Client\Curl;

class Response
{
    /**
     * @var array
     */
    public $body;

    /**
     * @var int
     */
    public $httpCode;

    /**
     * @var bool
     */
    public $status;
}
