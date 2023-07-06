<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\CommandHandler;

use Hook;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Association;
use Ps_accounts;

class UnlinkShopHandler
{
    /**
     * @var Association
     */
    private $association;

    public function __construct(Association $shopAccount)
    {
        $this->association = $shopAccount;
    }

    public function handle(UnlinkShopCommand $command): void
    {
        // FIXME: exec in shop context with $command->shopId

        $hookData = [
            'shopUuid' => $this->association->getShopSession()->getToken()->getUuid(),
            'shopId' => $command->shopId,
        ];

        $this->association->resetLink();

        Hook::exec(Ps_accounts::HOOK_ACTION_SHOP_ACCOUNT_UNLINK_AFTER, $hookData);
    }
}
