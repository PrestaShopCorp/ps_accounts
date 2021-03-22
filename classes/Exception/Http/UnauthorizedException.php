<?php

namespace PrestaShop\Module\PsAccounts\Exception\Http;

use Throwable;

class UnauthorizedException extends HttpException
{
    public function __construct($message = 'Unauthorized', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->statusCode = 401;
    }
}
