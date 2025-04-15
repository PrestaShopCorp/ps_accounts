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

use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\IdentityCreated;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;

class CreateIdentityHandler
{
    /**
     * @var AccountsService
     */
    private $accountsService;

    /**
     * @var OAuth2Client
     */
    private $oAuth2Client;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var StatusManager
     */
    private $shopStatus;

    /**
     * @param AccountsService $accountsService
     * @param ShopProvider $shopProvider
     * @param OAuth2Client $oauth2Client
     * @param StatusManager $shopStatus
     */
    public function __construct(
        AccountsService $accountsService,
        ShopProvider $shopProvider,
        OAuth2Client $oauth2Client,
        StatusManager $shopStatus
    ) {
        $this->accountsService = $accountsService;
        $this->shopProvider = $shopProvider;
        $this->oAuth2Client = $oauth2Client;
        $this->shopStatus = $shopStatus;
    }

    /**
     * @param CreateIdentityCommand $command
     *
     * @return IdentityCreated
     *
     * @throws AccountsException
     */
    public function handle(CreateIdentityCommand $command)
    {
//        if ($this->isAlreadyCreated()) {
//            return;
//        }

        $shopId = $command->shopId ?: \Shop::getContextShopID();

        $identityCreated = $this->accountsService->createShopIdentity(
            $this->shopProvider->getUrl($shopId)
        );

        $this->oAuth2Client->update(
            $identityCreated->clientId,
            $identityCreated->clientSecret
        );

        return $identityCreated;
    }

//    /**
//     * Idempotency check
//     *
//     * @return bool
//     */
//    private function isAlreadyCreated()
//    {
//        // FIXME: define where this code belongs
//        return $this->oAuth2Client->exists() && $this->shopStatus->exists();
//    }
}
