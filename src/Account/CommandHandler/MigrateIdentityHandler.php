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

use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentitiesCommand;
use PrestaShop\Module\PsAccounts\Account\Command\MigrateIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;

class MigrateIdentityHandler
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
     * @param AccountsService $accountsService
     * @param OAuth2Service $oAuth2Service
     * @param ShopProvider $shopProvider
     * @param StatusManager $shopStatus
     * @param ProofManager $proofManager
     * @param ConfigurationRepository $configurationRepository
     * @param CommandBus $commandBus
     */
    public function __construct(
        AccountsService $accountsService,
        OAuth2Service $oAuth2Service,
        ShopProvider $shopProvider,
        StatusManager $shopStatus,
        ProofManager $proofManager,
        ConfigurationRepository $configurationRepository,
        CommandBus $commandBus
    ) {
        $this->accountsService = $accountsService;
        $this->oAuth2Service = $oAuth2Service;
        $this->shopProvider = $shopProvider;
        $this->statusManager = $shopStatus;
        $this->proofManager = $proofManager;
        $this->configurationRepository = $configurationRepository;
        $this->commandBus = $commandBus;
    }

    /**
     * @param MigrateIdentityCommand $command
     *
     * @return void
     */
    public function handle(MigrateIdentityCommand $command)
    {
        Logger::getInstance()->info(
                    sprintf(
                        'Migrating identity for shop'
                    )
                );
        $shopId = $command->shopId ?: \Shop::getContextShopID();

        $shopUuid = $this->configurationRepository->getShopUuid();

        $lastUpgradedVersion = $this->configurationRepository->getLastUpgrade();

        $this->statusManager->setCloudShopId($shopUuid);

        Logger::getInstance()->info(
                    sprintf(
                        'Migrating identity for shop %s (%s)',
                        $shopUuid,
                        $lastUpgradedVersion
                    )
                );

        try {

            if ($shopUuid && version_compare($lastUpgradedVersion, '8', '<')) {

                Logger::getInstance()->info(
                    sprintf(
                        'Migrating identity for shop %s (%s)',
                        $shopUuid,
                        $lastUpgradedVersion
                    )
                );

                if ($this->configurationRepository->getLastUpgrade()) {
                    $token = $this->oAuth2Service->getAccessTokenByClientCredentials([], [
                        // audience v7
                        'shop_' . $shopUuid,
                    ])->access_token;
                } else {
                    $token = $this->accountsService->refreshShopToken(
                        $this->configurationRepository->getFirebaseRefreshToken(),
                        $shopUuid
                    )->token;
                }

                $identityCreated = $this->accountsService->migrateShopIdentity(
                    $shopUuid,
                    $token,
                    $this->shopProvider->getUrl($shopId),
                    $this->proofManager->generateProof(),
                    (string) $this->configurationRepository->getLastUpgrade()
                );

                if (
                    !empty($identityCreated->clientId) &&
                    !empty($identityCreated->clientSecret)
                ) {
                    $this->oAuth2Service->getOAuth2Client()->update(
                        $identityCreated->clientId,
                        $identityCreated->clientSecret
                    );
                }

                // cleanup obsolete token
                $this->configurationRepository->updateAccessToken('');

                // update ps_accounts version
                $this->configurationRepository->updateLastUpgrade(\Ps_accounts::VERSION);
            } else {
                Logger::getInstance()->info(
                    sprintf(
                        'Create identity for shop %s (%s)',
                        $shopUuid,
                        $lastUpgradedVersion
                    )
                );
                // TODO: how to verify if a shop is unintentionally dissociated?
                $this->commandBus->handle(new CreateIdentitiesCommand());
            }
        } catch (OAuth2Exception $e) {
            Logger::getInstance()->info(
                $e->getMessage()
            );
        } catch (AccountsException $e) {
            Logger::getInstance()->info(
                $e->getMessage()
            );
        }
    }
}
