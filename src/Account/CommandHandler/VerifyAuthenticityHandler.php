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

use PrestaShop\Module\PsAccounts\Account\Command\VerifyAuthenticityCommand;
use PrestaShop\Module\PsAccounts\Account\ManageProof;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\ShopIdentity;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Log\Logger;

class VerifyAuthenticityHandler
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
     * @var ShopIdentity
     */
    private $shopIdentity;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

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
     * @param ShopIdentity $shopIdentity
     * @param Oauth2Client $oauth2Client
     * @param ShopSession $shopSession
     * @param ManageProof $manageProof
     */
    public function __construct(
        AccountsClient $accountsClient,
        ShopProvider $shopProvider,
        ShopIdentity $shopIdentity,
        Oauth2Client $oauth2Client,
        ShopSession $shopSession,
        ManageProof $manageProof
    ) {
        $this->accountsClient = $accountsClient;
        $this->shopProvider = $shopProvider;
        $this->shopIdentity = $shopIdentity;
        $this->oauth2Client = $oauth2Client;
        $this->shopSession = $shopSession;
        $this->manageProof = $manageProof;
    }

    /**
     * @param VerifyAuthenticityCommand $command
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function handle(VerifyAuthenticityCommand $command)
    {
        if (!$this->oauth2Client->exists()) {
            // TODO: call Create Identity Command ? or just log ? or throw ? or juste remove this condition ?
            return;
        }

        // TODO: Que faire si on arrive pas obtenir un token ?
        $token = $this->shopSession->getValidToken();

        $proof = $this->manageProof->generateProof();

        $response = $this->accountsClient->verifyUrlAuthenticity(
            $this->shopIdentity->getShopUuid(),
            $token,
            $this->shopProvider->getUrl($command->shopId),
            $proof
        );
        if ($response['status'] === true && $response['body']) {
            // TODO: get the first token with verified scope or clear the token in configuration table ?
        } else {
            // TODO Add bad request handling here
            Logger::getInstance()->error($response);
        }
    }
}
