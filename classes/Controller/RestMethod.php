<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use PrestaShop\Module\PsAccounts\Enum;

class RestMethod extends Enum
{
    const INDEX = 'index';
    const STORE = 'store';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const SHOW = 'show';
}
