<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\VerifyIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\ShopUrl;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

class VerifyIdentityHandler
{
    /**
     * @var AccountsService
     */
    private $accountsService;

    /**
     * @var StatusManager
     */
    private $statusManager;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var ProofManager
     */
    private $proofManager;

    /**
     * @param AccountsService $accountsService
     * @param ShopProvider $shopProvider
     * @param StatusManager $statusManager
     * @param ShopSession $shopSession
     * @param ProofManager $proofManager
     */
    public function __construct(
        AccountsService $accountsService,
        ShopProvider $shopProvider,
        StatusManager $statusManager,
        ShopSession $shopSession,
        ProofManager $proofManager
    ) {
        $this->accountsService = $accountsService;
        $this->shopProvider = $shopProvider;
        $this->statusManager = $statusManager;
        $this->shopSession = $shopSession;
        $this->proofManager = $proofManager;
    }

    /**
     * @param VerifyIdentityCommand $command
     *
     * @return void
     *
     * @throws RefreshTokenException
     * @throws UnknownStatusException
     * @throws AccountsException
     */
    public function handle(VerifyIdentityCommand $command)
    {
        $shopId = $command->shopId ?: \Shop::getContextShopID();
        $status = $this->statusManager->getStatus(false, StatusManager::CACHE_TTL, $command->source);
        $cloudShopUrl = ShopUrl::createFromStatus($status, $shopId);

        if (!$command->force && $status->isVerified) {
            return;
        }

        try {
            if (!$command->force && !$cloudShopUrl->frontendUrlEquals($this->shopProvider->getUrl($shopId))) {
                return;
            }
        } catch (\InvalidArgumentException $e) {
            Logger::getInstance()->error($e->getMessage());

            return;
        }

        $this->accountsService->verifyShopIdentity(
            $this->statusManager->getCloudShopId(),
            $this->shopSession->getValidToken(),
            $this->shopProvider->getUrl($shopId),
            $this->shopProvider->getName($shopId),
            $this->proofManager->generateProof(),
            $command->origin,
            $command->source
        );
        $this->statusManager->invalidateCache();
    }
}
