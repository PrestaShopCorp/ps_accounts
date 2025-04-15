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
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;

class CheckStatusHandler
{
    /**
     * @var AccountsService
     */
    private $accountsService;

    /**
     * @var StatusManager
     */
    private $statusManager;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @param AccountsService $accountsService
     * @param StatusManager $statusManager
     * @param ShopSession $shopSession
     */
    public function __construct(
        AccountsService $accountsService,
        StatusManager $statusManager,
        ShopSession $shopSession
    ) {
        $this->accountsService = $accountsService;
        $this->statusManager = $statusManager;
        $this->shopSession = $shopSession;
    }

    /**
     * @param CheckStatusCommand $command
     *
     * @return ShopStatus
     *
     * @throws AccountsException
     * @throws RefreshTokenException
     */
    public function handle(CheckStatusCommand $command)
    {
//        $scp = $this->shopSession->getValidToken()->getJwt()->claims()->get('scp');
//        $scp = is_array($scp) ? $scp : [];
//
//        return in_array('shop.verified', $scp);

        // TODO: CircuitBreaker for that specific call with cached Response
        // TODO: implement cache ?
        ///** @var ConfigurationRepository $configuration */
        //$configuration = null;
        //if (time() - $configuration->getShopUuidDateUpd() > $command->cacheTtl) {
        return $this->accountsService->shopStatus(
            $this->statusManager->getShopUuid(),
            $this->shopSession->getValidToken()
        );
        //}
    }
}
