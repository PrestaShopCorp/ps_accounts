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
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Identity\Domain\IdentityManager;
use PrestaShop\Module\PsAccounts\Identity\Domain\OAuth2Client;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;

class CreateIdentityHandler
{
    /**
     * @var AccountsClient
     */
    private $accountsClient;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var IdentityManager
     */
    private $identityManager;

    /**
     * @param AccountsClient $accountsClient
     * @param ShopProvider $shopProvider
     * @param IdentityManager $identityManager
     */
    public function __construct(
        AccountsClient $accountsClient,
        ShopProvider $shopProvider,
        IdentityManager $identityManager
    ) {
        $this->accountsClient = $accountsClient;
        $this->shopProvider = $shopProvider;
        $this->identityManager = $identityManager;
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

        $identity = $this->identityManager->get($command->shopId);

        if (!$identity->hasOAuth2Client()) {
            $response = $this->accountsClient->createShopIdentity(
                $this->shopProvider->getUrl($command->shopId)
            );

            if ($response['status'] === true && isset($response['body'])) {
                $body = $response['body'];

                $oauth2Client = new OAuth2Client($body['clientId'], $body['clientSecret']);
                $identity->create($body['cloudShopId'], $oauth2Client);

                $this->identityManager->save($identity);
            } else {
                // TODO Add bad request handling here
            }
        }
    }
}
