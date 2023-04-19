<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Account;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;

class UpdateShopHandler
{
    /**
     * @var AccountsClient
     */
    private $accountClient;

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var Account
     */
    private $shopAccount;

    public function __construct(
        AccountsClient $accountClient,
        ShopContext $shopContext,
        Account $shopAccount
    )
    {
        $this->accountClient = $accountClient;
        $this->shopContext = $shopContext;
        $this->shopAccount = $shopAccount;
    }

    /**
     * @throws \Exception
     */
    public function handle(UpdateShop $command): array
    {
        return $this->shopContext->execInShopContext((int) $command->payload->shopId, function () use ($command) {

            if (!$this->shopAccount->isLinked()) {
                return null;
            }

            $shopToken = $this->shopAccount->getShopSession()->getOrRefreshToken();
            $ownerToken = $this->shopAccount->getOwnerSession()->getOrRefreshToken();

            return $this->accountClient->updateUserShop(
                $ownerToken->getUuid(),
                $shopToken->getUuid(),
                $ownerToken->getToken(),
                $command->payload
            );
        });
    }
}
