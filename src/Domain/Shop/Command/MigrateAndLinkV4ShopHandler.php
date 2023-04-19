<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;

class MigrateAndLinkV4ShopHandler
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

    public function __construct(
        AccountsClient $accountClient,
        ShopContext $shopContext,
        ShopSession $shopSession,
    )
    {
        $this->accountClient = $accountClient;
        $this->shopContext = $shopContext;
        $this->shopSession = $shopSession;
    }

    /**
     * @throws \Exception
     */
    public function handle(MigrateAndLinkv4Shop $command): array
    {
        return $this->shopContext->execInShopContext((int) $command->shopId, function () use ($command) {

            $shopToken = $this->shopSession->getOrRefreshToken();

            return $this->accountClient->reonboardShop(
                $shopToken->getUuid(),
                $shopToken->getToken(),
                $command->payload
            );
        });
    }
}
