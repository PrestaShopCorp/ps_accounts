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
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;

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
     * @return Token|false
     */
    public function handle(VerifyAuthenticityCommand $command)
    {
        try {
            if ($this->isAlreadyVerified()) {
                return $this->shopSession->getValidToken();
            }

            $shopId = $command->shopId ?: \Shop::getContextShopID();

            $response = $this->accountsClient->verifyUrlAuthenticity(
                $this->shopIdentity->getShopUuid(),
                $this->shopSession->getValidToken(),
                $this->shopProvider->getUrl($shopId),
                $this->manageProof->generateProof()
            );

            if ($response['status'] === true) {
                return $this->shopSession->getValidToken(true);
            }
        } catch (RefreshTokenException $e) {
        }

        return false;
    }

    /**
     * Idempotency check
     *
     * @return bool
     */
    private function isAlreadyVerified()
    {
        $response = $this->accountsClient->shopStatus(
            $this->shopIdentity->getShopUuid(),
            $this->shopSession->getValidToken()
        );
        // FIXME: todo
        return false;
//        // FIXME: factoriser
//        // FIXME: replace with firebase/php-jwt
//        $scp = $this->shopSession->getValidToken()->getJwt()->claims()->get('scp');
//        $scp = is_array($scp) ? $scp : [];
//
//        return in_array('shop.verified', $scp);
    }
}
