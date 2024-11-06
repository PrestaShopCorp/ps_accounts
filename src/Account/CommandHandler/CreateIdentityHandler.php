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
use PrestaShop\Module\PsAccounts\Account\ShopIdentity;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Log\Logger;

class CreateIdentityHandler
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
     * @var ShopIdentity
     */
    private $shopIdentity;

    /**
     * @param AccountsClient $accountsClient
     * @param ShopProvider $shopProvider
     * @param Oauth2Client $oauth2Client
     * @param ShopIdentity $shopIdentity
     */
    public function __construct(
        AccountsClient $accountsClient,
        ShopProvider $shopProvider,
        Oauth2Client $oauth2Client,
        ShopIdentity $shopIdentity
    ) {
        $this->accountsClient = $accountsClient;
        $this->shopProvider = $shopProvider;
        $this->oauth2Client = $oauth2Client;
        $this->shopIdentity = $shopIdentity;
    }

    /**
     * @param CreateIdentityCommand $command
     *
     * @return void
     */
    public function handle(CreateIdentityCommand $command)
    {
        // FIXME: remove that test
        // FIXME: migration from v7 -> v8 event modeling
        // - cleanup configuration storage
        // - identify shop (when ?) -> be sure we send version with it & when to trigger it ?
        // - UX associated ?
        // - Migrate routes using user token
        if (!$this->oauth2Client->exists()) {
            $response = $this->accountsClient->createShopIdentity(
                $this->shopProvider->getUrl($command->shopId)
            );

            if ($response['status'] === true && isset($response['body'])) {
                $body = $response['body'];
                // FIXME: should we refactor those kind of "entities" ?
                // FIXME: oauthClientRepository->getClientByShopId ?
                // FIXME: shopIdentityRepository->getIdentityByShopId ?
                $this->oauth2Client->update($body['clientId'], $body['clientSecret']);
                $this->shopIdentity->setShopUuid($body['cloudShopId']);
            } else {
                // TODO Add bad request handling here
                Logger::getInstance()->error($response);
            }
        }
    }
}
