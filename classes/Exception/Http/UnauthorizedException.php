<?php

namespace PrestaShop\Module\PsAccounts\Exception\Http;

use Throwable;

class UnauthorizedException extends HttpException
{
    /**
     * UnauthorizedException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = 'Unauthorized', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->statusCode = 401;
    }
}
