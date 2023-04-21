<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Account;

class UnlinkShopHandler
{
    /**
     * @var Account
     */
    private $shopAccount;

    public function __construct(Account $shopAccount)
    {
        $this->shopAccount = $shopAccount;
    }

    public function handle(UnlinkShop $command): void
    {
        // FIXME: exec in shop context with $command->shopId

        /* @var UnlinkShop $command */
        $this->shopAccount->resetLink();
    }
}
