<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\QueryHandler;

use PrestaShop\Module\PsAccounts\Domain\Account\Entity\AccountSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Query\GetOrRefreshShopToken;

class GetOrRefreshAccessTokenHandler
{
    /**
     * @var AccountSession
     */
    private $accountSession;

    public function __construct(AccountSession $accountSession)
    {
        $this->accountSession = $accountSession;
    }

    /**
     * @throws \Exception
     */
    public function handle(GetOrRefreshShopToken $query): string
    {
        return $this->accountSession->getOrRefreshAccessToken();
    }
}
