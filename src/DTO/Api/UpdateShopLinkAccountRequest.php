<?php

namespace PrestaShop\Module\PsAccounts\DTO\Api;

use PrestaShop\Module\PsAccounts\DTO\AbstractDto;

class UpdateShopLinkAccountRequest extends AbstractDto
{
    /** @var string */
    public $shop_id;
    /** @var string */
    public $shop_refresh_token;
    /** @var string */
    public $user_refresh_token;
    /** @var string */
    public $shop_token;
    /** @var string */
    public $user_token;
    /** @var string */
    public $employee_id;
}
