<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\CommandHandler;

use Hook;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Account;
use Ps_accounts;

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

    public function handle(UnlinkShopCommand $command): void
    {
        // FIXME: exec in shop context with $command->shopId

        $hookData = [
            'shopUuid' => $this->shopAccount->getShopSession()->getToken()->getUuid(),
            'shopId' => $command->shopId,
        ];

        $this->shopAccount->resetLink();

        Hook::exec(Ps_accounts::HOOK_ACTION_SHOP_ACCOUNT_UNLINK_AFTER, $hookData);
    }
}
