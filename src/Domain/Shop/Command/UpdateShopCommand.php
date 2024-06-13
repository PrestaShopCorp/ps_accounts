<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Dto\UpdateShop;

class UpdateShopCommand
{
    /**
     * @var UpdateShop
     */
    public $payload;

    public function __construct(UpdateShop $payload)
    {
        $this->payload = $payload;
    }
}
