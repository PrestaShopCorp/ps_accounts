<?php

namespace PrestaShop\Module\PsAccounts\Exception\Http;

class HttpException extends \RuntimeException
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
