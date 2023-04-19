<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

class MigrateAndLinkV4Shop
{
    /**
     * @var int
     */
    public $shopId;

    /**
     * @var array
     */
    public $payload;

    public function __construct(int $shopId, array $payload)
    {
        $this->shopId = $shopId;
        $this->payload = $payload;
    }
}
