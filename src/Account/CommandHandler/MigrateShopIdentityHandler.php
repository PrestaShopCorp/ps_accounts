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

use PrestaShop\Module\PsAccounts\Account\Command\MigrateShopIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;

class MigrateShopIdentityHandler
{
    /**
     * @var AccountsService
     */
    private $accountsService;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var StatusManager
     */
    private $statusManager;

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    /**
     * @var OAuth2Service
     */
    protected $oAuth2Service;

    /**
     * @param AccountsService $accountsService
     * @param ShopProvider $shopProvider
     * @param StatusManager $shopStatus
     * @param ConfigurationRepository $configurationRepository
     * @param OAuth2Service $oAuth2Service
     */
    public function __construct(
        AccountsService $accountsService,
        ShopProvider $shopProvider,
        StatusManager $shopStatus,
        ConfigurationRepository $configurationRepository,
        OAuth2Service $oAuth2Service
    ) {
        $this->accountsService = $accountsService;
        $this->shopProvider = $shopProvider;
        $this->statusManager = $shopStatus;
        $this->configurationRepository = $configurationRepository;
        $this->oAuth2Service = $oAuth2Service;
    }

    /**
     * @param MigrateShopIdentityCommand $command
     *
     * @return void
     *
     * @throws RefreshTokenException
     * @throws UnknownStatusException
     * @throws AccountsException
     */
    public function handle(MigrateShopIdentityCommand $command)
    {
        $shopId = $command->shopId ?: \Shop::getContextShopID();

        $shopUuid = $this->configurationRepository->getShopUuid();

        // TODO: reprise d'upgrade
        // FIXME: nettoyer les données à 'l'uninstall ?
        // FIXME: clearer les tokens au reset ?

        try {
            $accessToken = $this->oAuth2Service->getAccessTokenByClientCredentials([], [
                // audience v7
                'shop_' . $shopUuid
            ]);
        } catch (OAuth2Exception $e) {
            // IF audience invalide -> v5 OU v6 (ou bien déjà en v8 ??)
            /*try {
                //$this->accountsService->refreshTokens
            } catch (AccountsException $e) {
                // TODO ?? Création d'identité ??
                // backup en amont en cas de crash
            }*/
        }

        // TODO: Plus de nécessité de fournir un bearer
        // TODO: vérification de l'url enregistrée coté cloud SANS modification possible
        // TODO: reprise d'upgrade: last_upgraded_version

        $identityCreated = $this->accountsService->migrateShopIdentity(
            $shopUuid,
            $accessToken->access_token,
            $this->shopProvider->getUrl($shopId)
        );

        // TODO: nettoyage des vieux tokens
        $this->configurationRepository->updateAccessToken('');

        // TODO
//        $this->oAuth2Client->update(
//            $identityCreated->clientId,
//            $identityCreated->clientSecret
//        );

        $this->statusManager->setCloudShopId($identityCreated->cloudShopId);
    }
}
