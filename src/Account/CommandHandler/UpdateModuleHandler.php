<?php

namespace PrestaShop\Module\PsAccounts\Account\CommandHandler;

use Exception;
use PrestaShop\Module\PsAccounts\Account\Command\UpdateModuleCommand;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;

class UpdateModuleHandler
{
    /**
     * @var LinkShop
     */
    private $linkShop;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var AccountsClient
     */
    private $accountsClient;

    public function __construct(
        AccountsClient  $accountsClient,
        LinkShop $linkShop,
        ShopSession $shopSession
    ) {
        $this->accountsClient = $accountsClient;
        $this->linkShop = $linkShop;
        $this->shopSession = $shopSession;
    }

    /**
     * @param UpdateModuleCommand $command
     *
     * @return void
     */
    public function handle(UpdateModuleCommand $command)
    {
        $this->accountsClient->updateShopModule(
            $this->linkShop->getShopUuid(),
            (string)$this->shopSession->getToken(),
            $command->payload
        );
    }
}
