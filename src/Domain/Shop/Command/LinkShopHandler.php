<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class LinkShopHandler
{
    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var OwnerSession
     */
    private $ownerSession;

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    public function __construct(
        ShopSession             $shopSession,
        OwnerSession            $ownerSession,
        ConfigurationRepository $configurationRepository
    ) {
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * @throws RefreshTokenException
     */
    public function handle(LinkShop $command): void
    {
        $payload = $command->payload;

        if ($command->verifyTokens) {
            if (false === $this->shopSession->verifyToken($payload->shop_token)) {
                $payload->shop_token = $this->shopSession->refreshToken($payload->shop_refresh_token);
            }
            if (false === $this->ownerSession->verifyToken($payload->user_token)) {
                $payload->user_token = $this->ownerSession->refreshToken($payload->user_refresh_token);
            }
        }

        $this->shopSession->setToken($payload->shop_token, $payload->shop_refresh_token);
        $this->ownerSession->setToken($payload->user_token, $payload->user_refresh_token);
        $this->ownerSession->setEmployeeId($payload->employee_id);
        $this->configurationRepository->updateLoginEnabled(true);
    }
}
