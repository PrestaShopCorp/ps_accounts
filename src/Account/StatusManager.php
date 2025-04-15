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

namespace PrestaShop\Module\PsAccounts\Account;

use PrestaShop\Module\PsAccounts\Account\Command\CheckStatusCommand;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;

class StatusManager
{
    /**
     * Status Cache TTL in seconds
     */
    const STATUS_TTL = 10;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var ShopStatus
     */
    private $shopStatus;

    /**
     * @param CommandBus $commandBus
     */
    public function __construct(
        CommandBus $commandBus
    ) {
        $this->commandBus = $commandBus;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return !empty($this->getStatus()->cloudShopId);
    }

    /**
     * @return ShopStatus
     *
     * @throws RefreshTokenException
     * @throws AccountsException
     */
    public function getStatus()
    {
        if (null === $this->shopStatus) {
            $this->shopStatus = $this->commandBus->handle(
                new CheckStatusCommand(self::STATUS_TTL)
            );
        }

        return $this->shopStatus;
    }

    /**
     * @return bool
     *
     * @throws RefreshTokenException
     * @throws AccountsException
     */
    public function isVerified()
    {
        return $this->getStatus()->isVerified;
    }

    /**
     * @return string
     *
     * @throws RefreshTokenException
     * @throws AccountsException
     */
    public function getShopUuid()
    {
        return $this->getStatus()->cloudShopId;
    }

    /**
     * @return string
     *
     * @throws RefreshTokenException
     * @throws AccountsException
     */
    public function getOwnerUuid()
    {
        return $this->getStatus()->pointOdContactUid;
    }

    /**
     * @return string
     *
     * @throws RefreshTokenException
     * @throws AccountsException
     */
    public function getOwnerEmail()
    {
        return $this->getStatus()->pointOdContactEmail;
    }
}
