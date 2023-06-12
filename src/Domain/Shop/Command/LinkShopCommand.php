<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Domain\Shop\LinkShop;

class LinkShopCommand
{
    /**
     * @var LinkShop
     */
    public $payload;

    public function __construct(LinkShop $payload)
    {
        $this->payload = $payload;
    }
}
