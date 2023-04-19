<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Dto\Api\UpdateShopLinkAccountRequest;

class LinkShop
{
    /**
     * @var UpdateShopLinkAccountRequest
     */
    public $payload;

    /**
     * @var bool
     */
    public $verifyTokens = false;

    public function __construct(UpdateShopLinkAccountRequest $payload, bool $verifyTokens)
    {
        $this->payload = $payload;
        $this->verifyTokens = $verifyTokens;
    }
}
