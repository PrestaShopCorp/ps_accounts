<?php

namespace PrestaShop\Module\PsAccounts\DTO\Api;

use PrestaShop\Module\PsAccounts\DTO\AbstractDto;

class UpdateShopHmacRequest extends AbstractDto
{
    /** @var string */
    public $shop_id;
    /** @var string */
    public $hmac;
}
