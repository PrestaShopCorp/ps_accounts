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

use PrestaShop\Module\PsAccounts\Account\Command\Oauth2InstallCommand;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class Oauth2InstallHandler
{
    /**
     * @var AccountsClient
     */
    private $accountsClient;

    /**
     * @var ConfigurationRepository
     */
    private $configRepo;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @param AccountsClient $accountsClient
     * @param ShopProvider $shopProvider
     * @param ConfigurationRepository $configurationRepository
     * @param ShopContext $shopContext
     */
    public function __construct(
        AccountsClient $accountsClient,
        ShopProvider $shopProvider,
        ConfigurationRepository $configurationRepository,
        ShopContext $shopContext
    )
    {
        $this->accountsClient = $accountsClient;
        $this->shopProvider = $shopProvider;
        $this->configRepo = $configurationRepository;
        $this->shopContext = $shopContext;
    }

    /**
     * @param Oauth2InstallCommand $command
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function handle(Oauth2InstallCommand $command)
    {
        $this->shopContext->execInShopContext($command->shopId, function () use ($command) {
            if (!$this->configRepo->getOauth2ClientId() || !$this->configRepo->getOauth2ClientSecret()) {
                $currentShop = $this->shopProvider->getCurrentShop();
                $url = rtrim($currentShop['frontUrl'], '/');
                $backOfficeUrl = explode('/index.php', $currentShop['url'])[0];
                $resp = $this->accountsClient->createOauth2Client($backOfficeUrl, $url, intval($currentShop['id']));
                if ($resp['status'] === true && $resp['body']) {
                    $this->configRepo->updateOauth2ClientId($resp['body']['clientId']);
                    $this->configRepo->updateOauth2ClientSecret($resp['body']['clientSecret']);
                    $this->configRepo->updateShopUuid($resp['body']['cloudShopId']);
                } else {
                    // TODO Add bad request handling here
                }
            }
        });
    }
}
