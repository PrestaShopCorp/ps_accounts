<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

class RemoteUnlinkShopCommand
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
