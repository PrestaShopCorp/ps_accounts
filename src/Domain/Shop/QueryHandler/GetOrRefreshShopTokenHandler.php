<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\QueryHandler;

use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Token;
use PrestaShop\Module\PsAccounts\Domain\Shop\Query\GetOrRefreshShopToken;

class GetOrRefreshShopTokenHandler
{
    /**
     * @var ShopSession
     */
    private $shopSession;

    public function __construct(ShopSession $shopSession)
    {
        $this->shopSession = $shopSession;
    }

    /**
     * @throws \Exception
     */
    public function handle(GetOrRefreshShopToken $query): Token
    {
        return $this->shopSession->getOrRefreshToken();
    }
}
