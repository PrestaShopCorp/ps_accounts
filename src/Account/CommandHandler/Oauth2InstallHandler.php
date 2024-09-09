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
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;

class Oauth2InstallHandler
{
    /**
     * @var AccountsClient
     */
    private $accountsClient;

    /**
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var LinkShop
     */
    private $linkShop;

    /**
     * @param AccountsClient $accountsClient
     * @param ShopProvider $shopProvider
     * @param Oauth2Client $oauth2Client
     * @param ShopContext $shopContext
     * @param LinkShop $linkShop
     */
    public function __construct(
        AccountsClient $accountsClient,
        ShopProvider $shopProvider,
        Oauth2Client $oauth2Client,
        ShopContext $shopContext,
        LinkShop $linkShop
    )
    {
        $this->accountsClient = $accountsClient;
        $this->shopProvider = $shopProvider;
        $this->oauth2Client = $oauth2Client;
        $this->shopContext = $shopContext;
        $this->linkShop = $linkShop;
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
            if (!$this->oauth2Client->exists()) {
                $currentShop = $this->shopProvider->getCurrentShop();
                $url = rtrim($currentShop['frontUrl'], '/');
                $backOfficeUrl = explode('/index.php', $currentShop['url'])[0];
                $resp = $this->accountsClient->createShopIdentity($backOfficeUrl, $url, intval($currentShop['id']));
                if ($resp['status'] === true && $resp['body']) {
                    $this->oauth2Client->update($resp['body']['clientId'], $resp['body']['clientSecret']);
                    $this->linkShop->setShopUuid($resp['body']['cloudShopId']);
                } else {
                    // TODO Add bad request handling here
                }
            }
        });
    }
}

