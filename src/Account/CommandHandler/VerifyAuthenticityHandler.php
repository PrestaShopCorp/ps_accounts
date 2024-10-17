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
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Identity\Domain\IdentityManager;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;

class VerifyAuthenticityHandler
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
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var IdentityManager
     */
    private $identityManager;

    /**
     * @var ManageProof
     */
    private $manageProof;

    /**
     * @param AccountsClient $accountsClient
     * @param ShopProvider $shopProvider
     * @param ShopSession $shopSession
     * @param IdentityManager $identityManager
     * @param ManageProof $manageProof
     */
    public function __construct(
        AccountsClient $accountsClient,
        ShopProvider $shopProvider,
        ShopSession $shopSession,
        IdentityManager $identityManager,
        ManageProof $manageProof
    ) {
        $this->accountsClient = $accountsClient;
        $this->shopProvider = $shopProvider;
        $this->shopSession = $shopSession;
        $this->identityManager = $identityManager;
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
        $identity = $this->identityManager->get();

        if (!$identity->hasOAuth2Client()) {
            // TODO: call Create Identity Command ? or just log ? or throw ? or juste remove this condition ?
            return;
        }

        // TODO: Que faire si on arrive pas obtenir un token ?
        $token = $this->shopSession->getValidToken();

        $proof = $this->manageProof->generateProof();

        $response = $this->accountsClient->verifyUrlAuthenticity(
            $identity->cloudShopId(),
            $token,
            $this->shopProvider->getUrl($command->shopId),
            $proof
        );
        if ($response['status'] === true && $response['body']) {
            $identity->verify();

            $this->identityManager->save($identity);

            // TODO: or just get the first token with verified scope or clear the token in configuration table ?
        } else {
            // TODO Add bad request handling here
        }
    }
}
