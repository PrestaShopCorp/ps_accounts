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
use PrestaShop\Module\PsAccounts\Account\Command\MigrateOrCreateIdentityV8Command;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\UpgradeService;

class MigrateOrCreateIdentityV8Handler
{
    /**
     * @var AccountsService
     */
    private $accountsService;

    /**
     * @var OAuth2Service
     */
    protected $oAuth2Service;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var StatusManager
     */
    private $statusManager;

    /**
     * @var ProofManager
     */
    protected $proofManager;

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var UpgradeService
     */
    private $upgradeService;

    /**
     * @param AccountsService $accountsService
     * @param OAuth2Service $oAuth2Service
     * @param ShopProvider $shopProvider
     * @param StatusManager $shopStatus
     * @param ProofManager $proofManager
     * @param ConfigurationRepository $configurationRepository
     * @param CommandBus $commandBus
     * @param UpgradeService $upgradeService
     */
    public function __construct(
        AccountsService $accountsService,
        OAuth2Service $oAuth2Service,
        ShopProvider $shopProvider,
        StatusManager $shopStatus,
        ProofManager $proofManager,
        ConfigurationRepository $configurationRepository,
        CommandBus $commandBus,
        UpgradeService $upgradeService
    ) {
        $this->accountsService = $accountsService;
        $this->oAuth2Service = $oAuth2Service;
        $this->shopProvider = $shopProvider;
        $this->statusManager = $shopStatus;
        $this->proofManager = $proofManager;
        $this->configurationRepository = $configurationRepository;
        $this->commandBus = $commandBus;
        $this->upgradeService = $upgradeService;
    }

    /**
     * @param MigrateOrCreateIdentityV8Command $command
     *
     * @return void
     *
     * @throws OAuth2Exception
     * @throws AccountsException
     * @throws RefreshTokenException
     * @throws UnknownStatusException
     */
    public function handle(MigrateOrCreateIdentityV8Command $command)
    {
        $shopId = $command->shopId ?: \Shop::getContextShopID();
        $shopUuid = $this->configurationRepository->getShopUuid();

        // FIXME: command can hold that property depending on context
        $fromVersion = $this->upgradeService->getRegisteredVersion();

        // FIXME: shouldn't this condition be a specific flag
        if (!$shopUuid || version_compare($fromVersion, '8', '>=')) {
            $this->upgradeService->setVersion();

            $this->commandBus->handle(new CreateIdentityCommand(
                $command->shopId,
                false,
                $command->origin,
                $command->source
            ));

            return;
        }

        // migrate cloudShopId locally
        $this->statusManager->setCloudShopId($shopUuid);

        if (version_compare($fromVersion, '7', '>=')) {
            $token = $this->getAccessTokenV7($shopUuid);
        } else {
            $token = $this->getFirebaseTokenV6($shopUuid);
        }

        try {
            $identityCreated = $this->accountsService->migrateShopIdentity(
                $shopUuid,
                $token,
                $this->shopProvider->getUrl($shopId),
                $this->shopProvider->getName($shopId),
                $fromVersion,
                $this->proofManager->generateProof(),
                $command->source
            );
        } catch (AccountsException $e) {
            if ($e->getErrorCode() !== AccountsException::ERROR_STORE_LEGACY_NOT_FOUND) {
                // Will trigger reset banner
                $this->upgradeService->setVersion('');
                // Will trigger new identity creation
                $this->statusManager->setCloudShopId('');
                $this->statusManager->setPointOfContactUuid('');
                $this->statusManager->setPointOfContactEmail('');
                // TODO: clear tokens using session
                $this->configurationRepository->updateAccessToken('');
            } else {
                throw $e;
            }
        }

        if (!empty($identityCreated->clientId) &&
            !empty($identityCreated->clientSecret)) {
            $this->oAuth2Service->getOAuth2Client()->update(
                $identityCreated->clientId,
                $identityCreated->clientSecret
            );
        }

        // cleanup obsolete token
        $this->configurationRepository->updateAccessToken('');

        $this->statusManager->invalidateCache();

        $this->upgradeService->setVersion();
    }

    /**
     * @param string $shopUuid
     *
     * @return string
     *
     * @throws OAuth2Exception
     */
    protected function getAccessTokenV7($shopUuid)
    {
        return $this->oAuth2Service->getAccessTokenByClientCredentials([], [
            // audience v7
            'shop_' . $shopUuid,
        ])->access_token;
    }

    /**
     * @param string $shopUuid
     *
     * @return string
     *
     * @throws AccountsException
     */
    protected function getFirebaseTokenV6($shopUuid)
    {
        return $this->accountsService->refreshShopToken(
            $this->configurationRepository->getFirebaseRefreshToken(),
            $shopUuid
        )->token;
    }
}
