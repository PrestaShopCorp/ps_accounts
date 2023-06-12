<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\CommandHandler;

use Hook;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\LinkShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShopException;
use Ps_accounts;

class LinkShopCommandHandler
{
    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var OwnerSession
     */
    private $ownerSession;

    public function __construct(
        ShopSession $shopSession,
        OwnerSession $ownerSession
    ) {
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
    }

    /**
     * @param LinkShopCommand $command
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function handle(LinkShopCommand $command): void
    {
        $payload = $command->payload;

        $this->shopSession->setToken($payload->shopToken, $payload->shopRefreshToken);
        $this->ownerSession->setToken($payload->userToken, $payload->userRefreshToken);
        $this->ownerSession->setEmployeeId((int) $payload->employeeId ?: null);

        Hook::exec(Ps_accounts::HOOK_ACTION_SHOP_ACCOUNT_LINK_AFTER, [
            'shopUuid' => $this->shopSession->getToken()->getUuid(),
            'shopId' => $command->payload->shopId,
        ]);
    }
}
