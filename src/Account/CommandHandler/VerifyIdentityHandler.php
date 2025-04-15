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
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;

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
    private $manageProof;

    /**
     * @param AccountsService $accountsService
     * @param ShopProvider $shopProvider
     * @param StatusManager $shopStatus
     * @param ShopSession $shopSession
     * @param ProofManager $manageProof
     */
    public function __construct(
        AccountsService $accountsService,
        ShopProvider $shopProvider,
        StatusManager $shopStatus,
        ShopSession $shopSession,
        ProofManager $manageProof
    ) {
        $this->accountsService = $accountsService;
        $this->shopProvider = $shopProvider;
        $this->statusManager = $shopStatus;
        $this->shopSession = $shopSession;
        $this->manageProof = $manageProof;
    }

    /**
     * @param VerifyIdentityCommand $command
     *
     * @return ShopStatus
     *
     * @throws AccountsException
     * @throws RefreshTokenException
     */
    public function handle(VerifyIdentityCommand $command)
    {
        $status = $this->statusManager->getStatus();

        if ($status->isVerified) {
            return $status;
        }

        $shopId = $command->shopId ?: \Shop::getContextShopID();

        return $this->accountsService->verifyShopProof(
            $status->cloudShopId,
            $this->shopSession->getValidToken(),
            $this->shopProvider->getUrl($shopId),
            $this->manageProof->generateProof()
        );
    }
}
