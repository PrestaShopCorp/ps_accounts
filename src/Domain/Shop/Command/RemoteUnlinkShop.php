<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

class RemoteUnlinkShop
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
