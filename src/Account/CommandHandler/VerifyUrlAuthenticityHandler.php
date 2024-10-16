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

use PrestaShop\Module\PsAccounts\Account\Command\VerifyUrlAuthenticityCommand;
use PrestaShop\Module\PsAccounts\Account\ManageProof;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Api\Client\ShopUrl;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;

class VerifyUrlAuthenticityHandler
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
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var ManageProof
     */
    private $manageProof;

    /**
     * @param AccountsClient $accountsClient
     * @param ShopProvider $shopProvider
     * @param Oauth2Client $oauth2Client
     * @param ShopContext $shopContext
     * @param ShopSession $shopSession
     * @param ManageProof $manageProof
     */
    public function __construct(
        AccountsClient $accountsClient,
        ShopProvider $shopProvider,
        Oauth2Client $oauth2Client,
        ShopContext $shopContext,
        ShopSession $shopSession,
        ManageProof $manageProof
    ) {
        $this->accountsClient = $accountsClient;
        $this->shopProvider = $shopProvider;
        $this->oauth2Client = $oauth2Client;
        $this->shopContext = $shopContext;
        $this->shopSession = $shopSession;
        $this->manageProof = $manageProof;
    }

    /**
     * @param VerifyUrlAuthenticityCommand $command
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function handle(VerifyUrlAuthenticityCommand $command)
    {
        $this->shopContext->execInShopContext($command->shopId, function () use ($command) {
            if (!$this->oauth2Client->exists()) {
                // TODO: call Create Identity Command ? or just log ? or throw ? or juste remove this condition ?
                return;
            }

            // TODO: Que faire si on arrive pas obtenir un token ?
            $token = $this->shopSession->getValidToken();

            $proof = $this->manageProof->generateProof();

            $currentShop = $this->shopProvider->getCurrentShop();

            $shopUrl = ShopUrl::createFromShopData($currentShop);

            $response = $this->accountsClient->verifyUrlAuthenticity(
                $currentShop['uuid'],
                $token,
                $this->shopProvider->getUrl($command->shopId),
                $proof
            );
            if ($response['status'] === true && $response['body']) {
                // TODO: get the first token with verified scope or clear the token in configuration table ?
            } else {
                // TODO Add bad request handling here
            }
        });
    }
}
