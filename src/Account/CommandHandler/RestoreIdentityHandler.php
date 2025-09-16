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

use PrestaShop\Module\PsAccounts\Account\Command\MigrateOrCreateIdentityV8Command;
use PrestaShop\Module\PsAccounts\Account\Command\RestoreIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Service\UpgradeService;

class RestoreIdentityHandler
{
    /**
     * @var OAuth2Client
     */
    private $oAuth2Client;

    /**
     * @var StatusManager
     */
    private $statusManager;

    /**
     * @var UpgradeService
     */
    private $upgradeService;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @param OAuth2Client $oauth2Client
     * @param StatusManager $shopStatus
     * @param UpgradeService $upgradeService
     * @param CommandBus $commandBus
     */
    public function __construct(
        OAuth2Client $oauth2Client,
        StatusManager $shopStatus,
        UpgradeService $upgradeService,
        CommandBus $commandBus
    ) {
        $this->oAuth2Client = $oauth2Client;
        $this->statusManager = $shopStatus;
        $this->upgradeService = $upgradeService;
        $this->commandBus = $commandBus;
    }

    /**
     * @param RestoreIdentityCommand $command
     *
     * @return void
     *
     * * @throws RefreshTokenException
     * * @throws UnknownStatusException
     * * @throws AccountsException
     */
    public function handle(RestoreIdentityCommand $command)
    {
        $shopId = $command->shopId ?: \Shop::getContextShopID();
        $this->oAuth2Client->update(
            $command->clientId,
            $command->clientSecret
        );
        $this->statusManager->setCloudShopId($command->cloudShopId);
        $this->statusManager->setIsVerified(false);
        $this->upgradeService->setVersion();

        $this->commandBus->handle(new MigrateOrCreateIdentityV8Command(
            $shopId,
            $command->origin,
            $command->source
        ));
//
//        $this->statusManager->invalidateCache();
    }
}
