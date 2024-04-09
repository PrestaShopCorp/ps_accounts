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

use PrestaShop\Module\PsAccounts\Account\Command\UpgradeModuleCommand;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class UpgradeModuleHandler
{
    /**
     * @var LinkShop
     */
    private $linkShop;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var AccountsClient
     */
    private $accountsClient;

    /**
     * @var ConfigurationRepository
     */
    private $configRepo;

    /**
     * @var ShopContext
     */
    private $shopContext;

    public function __construct(
        AccountsClient $accountsClient,
        LinkShop $linkShop,
        ShopSession $shopSession,
        ShopContext $shopContext,
        ConfigurationRepository $configurationRepository
    ) {
        $this->accountsClient = $accountsClient;
        $this->linkShop = $linkShop;
        $this->shopSession = $shopSession;
        $this->shopContext = $shopContext;
        $this->configRepo = $configurationRepository;
    }

    /**
     * @param UpgradeModuleCommand $command
     *
     * @return void
     */
    public function handle(UpgradeModuleCommand $command)
    {
        $this->shopContext->execInShopContext($command->payload->shopId, function () use ($command) {
            if ($this->configRepo->getLastUpgrade() !== \Ps_accounts::VERSION) {
                $this->configRepo->setLastUpgrade(\Ps_accounts::VERSION);
                // call to refresh shop firebase token at the moment, in the future, use oauth shop token
                $this->shopSession->setToken(
                    $this->getOrRefreshShopToken(),
                    $this->shopSession->getToken()->getRefreshToken()
                );

                $this->accountsClient->updateShopModule(
                    $this->linkShop->getShopUuid(),
                    (string) $this->shopSession->getToken(),
                    $command->payload
                );
            }
        });
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    private function getOrRefreshShopToken()
    {
        $token = $this->shopSession->getToken();
        if ($token->isExpired()) {
            $response = $this->accountsClient->refreshShopToken(
                //$this->configRepo->getFirebaseRefreshToken(),
                $this->shopSession->getToken()->getRefreshToken(),
                //$this->configRepo->getShopUuid()
                $this->linkShop->getShopUuid()
            );

            if (isset($response['body']['token'])) {
                return $response['body']['token'];
            }
        }

        return (string) $token;
    }
}
