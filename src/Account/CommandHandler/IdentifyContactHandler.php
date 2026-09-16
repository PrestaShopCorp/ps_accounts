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

use PrestaShop\Module\PsAccounts\Account\Command\IdentifyContactCommand;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

class IdentifyContactHandler
{
    /**
     * Module capability advertised to the shared front component (accounts-components)
     * through GetContextHandler: this module accepts a point of contact on a store
     * whose identity exists but is not verified yet (the isVerified guard was
     * removed in 8.0.18, ACC-3465). The component only shows the point-of-contact
     * block on an unverified store when it reads this capability, so a merchant on
     * an older module is never offered a sign-in the module would silently refuse.
     *
     * Capabilities are additive: absence means "not supported". This constant is
     * never removed, old module versions are never decommissioned.
     *
     * @var string
     */
    const CAPABILITY_POC_BEFORE_VERIFICATION = 'poc-before-verification';

    /**
     * @var AccountsService
     */
    private $accountsService;

    /**
     * @var StatusManager
     */
    private $statusManager;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var OwnerSession
     */
    private $ownerSession;

    /**
     * @param AccountsService $accountsService
     * @param StatusManager $statusManager
     * @param ShopSession $shopSession
     * @param OwnerSession $ownerSession
     */
    public function __construct(
        AccountsService $accountsService,
        StatusManager $statusManager,
        ShopSession $shopSession,
        OwnerSession $ownerSession
    ) {
        $this->accountsService = $accountsService;
        $this->statusManager = $statusManager;
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
    }

    /**
     * @param IdentifyContactCommand $command
     *
     * @return void
     *
     * @throws AccountsException
     */
    public function handle(IdentifyContactCommand $command)
    {
        $this->accountsService
            ->withSource($command->source)
            ->setPointOfContact(
                $this->statusManager->getCloudShopId(),
                $this->shopSession->getValidToken(),
                $command->accessToken->access_token
            );

        // cleanup user token
        $this->ownerSession->cleanup();

        // optimistic update cached status
        $this->statusManager->setPointOfContactUuid($command->userInfo->sub);
        $this->statusManager->setPointOfContactEmail($command->userInfo->email);
    }
}
