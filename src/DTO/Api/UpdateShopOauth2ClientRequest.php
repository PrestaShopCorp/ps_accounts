<?php

namespace PrestaShop\Module\PsAccounts\DTO\Api;

use PrestaShop\Module\PsAccounts\DTO\AbstractDto;

class UpdateShopOauth2ClientRequest extends AbstractDto
{
    /** @var string */
    public $shop_id;
    /** @var string */
    public $client_id;
    /** @var string */
    public $client_secret;
}
