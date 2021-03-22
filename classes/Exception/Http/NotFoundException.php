<?php

namespace PrestaShop\Module\PsAccounts\Exception\Http;

use Throwable;

class NotFoundException extends HttpException
{
    public function __construct($message = 'Not Found', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->statusCode = 404;
    }
}
