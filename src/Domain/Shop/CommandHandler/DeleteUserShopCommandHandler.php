<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\CommandHandler;

use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\DeleteUserShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;

class DeleteUserShopCommandHandler
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
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var OwnerSession
     */
    private $ownerSession;

    public function __construct(
        AccountsClient $accountClient,
        ShopContext $shopContext,
        ShopSession $shopSession,
        OwnerSession $ownerSession
    ) {
        $this->accountClient = $accountClient;
        $this->shopContext = $shopContext;
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
    }

    /**
     * @throws \Exception
     */
    public function handle(DeleteUserShopCommand $command): array
    {
        return $this->shopContext->execInShopContext((int) $command->shopId, function () {
            $ownerToken = $this->ownerSession->getOrRefreshToken();
            $shopToken = $this->shopSession->getOrRefreshToken();

            return $this->accountClient->deleteUserShop(
                $ownerToken->getUuid(),
                $shopToken->getUuid(),
                $ownerToken->getJwt()
            );
        });
    }
}