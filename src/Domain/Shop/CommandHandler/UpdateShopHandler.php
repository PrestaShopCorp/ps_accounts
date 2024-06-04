<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\CommandHandler;

use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\UpdateShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Association;

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
     * @var Association
     */
    private $association;

    public function __construct(
        AccountsClient $accountClient,
        ShopContext $shopContext,
        Association $shopAccount
    ) {
        $this->accountClient = $accountClient;
        $this->shopContext = $shopContext;
        $this->association = $shopAccount;
    }

    /**
     * @throws \Exception
     */
    public function handle(UpdateShopCommand $command): array
    {
        return $this->shopContext->execInShopContext((int) $command->payload->shopId, function () use ($command) {
            if (!$this->association->isLinked()) {
                return null;
            }

            $shopToken = $this->association->getShopSession()->getOrRefreshToken();
            $ownerToken = $this->association->getOwnerSession()->getOrRefreshToken();

            return $this->accountClient->updateUserShop(
                $ownerToken->getUuid(),
                $shopToken->getUuid(),
                $ownerToken->getJwt(),
                $command->payload
            );
        });
    }
}
