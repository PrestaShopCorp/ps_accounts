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

use PrestaShop\Module\PsAccounts\Account\Command\CleanupIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;

class CleanupIdentityHandler
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
     * @var ConfigurationRepository
     */
    private $repository;

    /**
     * @param OAuth2Client $oauth2Client
     * @param StatusManager $shopStatus
     * @param ConfigurationRepository $repository
     */
    public function __construct(
        OAuth2Client $oauth2Client,
        StatusManager $shopStatus,
        ConfigurationRepository $repository
    ) {
        $this->oAuth2Client = $oauth2Client;
        $this->statusManager = $shopStatus;
        $this->repository = $repository;
    }

    /**
     * @param CleanupIdentityCommand $command
     *
     * @return void
     */
    public function handle(CleanupIdentityCommand $command)
    {
        $this->statusManager->clearIdentity();
        $this->oAuth2Client->delete();
        $this->clearTokens();
    }

    /**
     * @return void
     */
    private function clearTokens()
    {
        $this->repository->updateAccessToken('');
        $this->repository->updateFirebaseIdAndRefreshTokens('', '');
        $this->repository->updateUserFirebaseIdAndRefreshToken('', '');
    }
}
