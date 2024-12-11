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

use PrestaShop\Module\PsAccounts\Account\Command\CheckStatusCommand;
use PrestaShop\Module\PsAccounts\Account\Dto\ShopStatus;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\ShopIdentity;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Exception\DtoException;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;

class CheckStatusHandler
{
    /**
     * @var AccountsClient
     */
    private $accountsClient;

    /**
     * @var ShopIdentity
     */
    private $shopIdentity;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @param AccountsClient $accountsClient
     */
    public function __construct(
        AccountsClient $accountsClient,
        ShopIdentity $shopIdentity,
        ShopSession $shopSession
    ) {
        $this->accountsClient = $accountsClient;
        $this->shopIdentity = $shopIdentity;
        $this->shopSession = $shopSession;
    }

    /**
     * @param CheckStatusCommand $command
     *
     * @return ShopStatus
     *
     * @throws DtoException
     * @throws RefreshTokenException
     */
    public function handle(CheckStatusCommand $command)
    {
//        $scp = $this->shopSession->getValidToken()->getJwt()->claims()->get('scp');
//        $scp = is_array($scp) ? $scp : [];
//
//        return in_array('shop.verified', $scp);

        $response = $this->accountsClient->shopStatus(
            $this->shopIdentity->getShopUuid(),
            $this->shopSession->getValidToken()
        );
        if ($response['status'] === true && is_array($response['body'])) {
            return new ShopStatus($response['body']);
        }

        return new ShopStatus();
    }
}
