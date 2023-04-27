<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Dto\UpdateShop as UpdateShopDto;

class UpdateShopCommand
{
    /**
     * @var UpdateShopDto
     */
    public $payload;

    public function __construct(UpdateShopDto $payload)
    {
        $this->payload = $payload;
    }
}
