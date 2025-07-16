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
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\PrestaShop\Adapter\Module\Module;

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
     * @param MigrateOrCreateIdentityV8Command $command
     *
     * @return void
     */
    public function handle(MigrateOrCreateIdentityV8Command $command)
    {
        $shopId = $command->shopId ?: \Shop::getContextShopID();
        $shopUuid = $this->configurationRepository->getShopUuid();

        $lastUpgradedVersion = $this->configurationRepository->getLastUpgrade(false);

        // FIXME: is it safe to read lastUpgradedVersion in Core DB only ?
        // - check if version is not updated when upgrade fails
        // - throws UpgradeFailedException
        if ($lastUpgradedVersion === '0') {
            $lastUpgradedVersion = $this->getLastRegisteredModuleVersion();
        }

        // FIXME: 1. upgrade from v5.6.2 -> 8
        // FIXME: 2. lastUpgradedVersion
        // FIXME: 3. class AbstractOAuthController
        // FIXME: 4. UpgradeCommand (specific upgrade cases ?)
        // FIXME: 5. ob_end_clean
        // FIXME: 6. token invalide avec shipping après redirect

        $e = null;
        try {
            if (!$shopUuid || version_compare($lastUpgradedVersion, '8', '>=')) {
                $this->upgradeVersionNumber();

                $this->commandBus->handle(new CreateIdentityCommand($command->shopId));

                return;
            }

            // migrate cloudShopId locally
            $this->statusManager->setCloudShopId($shopUuid);

            if (version_compare($lastUpgradedVersion, '7', '>=')) {
                $token = $this->getAccessTokenV7($shopUuid);
            } else {
                $token = $this->getFirebaseTokenV6($shopUuid);
            }

            $identityCreated = $this->accountsService->migrateShopIdentity(
                $shopUuid,
                $token,
                $this->shopProvider->getUrl($shopId),
                $this->proofManager->generateProof(),
                $lastUpgradedVersion
            );

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

            // update ps_accounts upgraded version
            $this->upgradeVersionNumber();
        } catch (OAuth2Exception $e) {
        } catch (AccountsException $e) {
        } catch (RefreshTokenException $e) {
        } catch (UnknownStatusException $e) {
        }

        if ($e) {
            Logger::getInstance()->error($e->getMessage());
        }
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

    /**
     * @return void
     */
    protected function upgradeVersionNumber()
    {
        $this->configurationRepository->updateLastUpgrade(\Ps_accounts::VERSION);
    }

    /**
     * @return string
     */
    public function getLastRegisteredModuleVersion()
    {
        return \Db::getInstance()->getValue(
            'SELECT version FROM ' . _DB_PREFIX_ . 'module WHERE name = \'ps_accounts\''
        );
    }
}
