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

use PrestaShop\Module\PsAccounts\Account\Command\UpdateBackOfficeUrlCommand;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\ShopUrl;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

class UpdateBackOfficeUrlHandler
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
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    /**
     * @param AccountsService $accountsService
     * @param StatusManager $statusManager
     * @param ShopProvider $shopProvider
     * @param ShopSession $shopSession
     * @param ConfigurationRepository $configurationRepository
     */
    public function __construct(
        AccountsService $accountsService,
        StatusManager $statusManager,
        ShopProvider $shopProvider,
        ShopSession $shopSession,
        ConfigurationRepository $configurationRepository
     ) {
        $this->accountsService = $accountsService;
        $this->statusManager = $statusManager;
        $this->shopProvider = $shopProvider;
        $this->shopSession = $shopSession;
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * @param UpdateBackOfficeUrlCommand $command
     *
     * @return void
     */
    public function handle(UpdateBackOfficeUrlCommand $command)
    {
        // TODO: rework multishop management
        $shopId = $command->shopId ?: \Shop::getContextShopID() ?: $this->configurationRepository->getMainShopId();

        // TODO: rework parameters priority
        $status = $this->statusManager->getStatus(false, StatusManager::CACHE_TTL, 'ps_accounts');

        $cloudShopUrl = ShopUrl::createFromStatus($status, $shopId);
        $localShopUrl = $this->shopProvider->getUrl($shopId);

        try {
            // Check if BO url changed and urls aren't empty
            if (!$cloudShopUrl->backOfficeUrlEquals($localShopUrl)) {
                $this->accountsService->updateBackOfficeUrl(
                    $status->cloudShopId,
                    $this->shopSession->getValidToken(),
                    $localShopUrl
                );
            }
        } catch (\InvalidArgumentException $e) {
            Logger::getInstance()->error($e->getMessage());
        }
    }
}
