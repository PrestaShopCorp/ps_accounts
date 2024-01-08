<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker;

use PrestaShop\Module\PsAccounts\Enum;

class State extends Enum
{
    const OPEN = 0;
    const CLOSED = 1;
    const HALF_OPEN = 2;
}
