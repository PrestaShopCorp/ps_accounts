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
use PrestaShop\Module\PsAccounts\Account\ShopIdentity;
use PrestaShop\Module\PsAccounts\Account\Session;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Log\Logger;

class UpgradeModuleHandler
{
    /**
     * @var ShopIdentity
     */
    private $shopIdentity;

    /**
     * @var Session\ShopSession
     */
    private $shopSession;

    /**
     * @var AccountsClient
     */
    private $accountsClient;

    public function __construct(
        AccountsClient          $accountsClient,
        ShopIdentity            $shopIdentity,
        Session\ShopSession     $shopSession
    ) {
        $this->accountsClient = $accountsClient;
        $this->shopIdentity = $shopIdentity;
        $this->shopSession = $shopSession;
    }

    /**
     * @param UpgradeModuleCommand $command
     *
     * @return void
     *
     * @throws RefreshTokenException
     */
    public function handle(UpgradeModuleCommand $command)
    {
        Logger::getInstance()->info(
            'attempt upgrade [' . $command->payload->version . ']'
        );

        $token = $this->shopSession->getValidToken();

        if (!$token->getJwt() instanceof NullToken) {
            // FIXME: Migrate to a Hydra Token compatible route
            $this->accountsClient->upgradeShopModule(
                $this->shopIdentity->getShopUuid(),
                (string) $token,
                $command->payload
            );
        }
    }
}
