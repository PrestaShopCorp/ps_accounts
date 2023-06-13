<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

class DeleteUserShopCommand
{
    /**
     * @var int
     */
    public $shopId;

    public function __construct(int $shopId)
    {
        $this->shopId = $shopId;
    }
}
