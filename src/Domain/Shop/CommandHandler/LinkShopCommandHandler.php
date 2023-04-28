<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\CommandHandler;

use Hook;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\LinkShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
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

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    public function __construct(
        ShopSession $shopSession,
        OwnerSession $ownerSession,
        ConfigurationRepository $configurationRepository
    ) {
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * @throws RefreshTokenException
     */
    public function handle(LinkShopCommand $command): void
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
        $this->ownerSession->setEmployeeId((int) $payload->employee_id ?: null);
        $this->configurationRepository->updateLoginEnabled(true);

        Hook::exec(Ps_accounts::HOOK_ACTION_SHOP_ACCOUNT_LINK_AFTER, [
            'shopUuid' => $this->shopSession->getToken()->getUuid(),
            'shopId' => $command->payload->shop_id,
        ]);
    }
}
