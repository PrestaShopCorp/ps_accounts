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
use PrestaShop\Module\PsAccounts\Account\Dto\ShopStatus;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ShopIdentity
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * ShopLinkAccountService constructor.
     *
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        ConfigurationRepository $configuration,
        CommandBus $commandBus
    ) {
        $this->configuration = $configuration;
        $this->commandBus = $commandBus;
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function delete()
    {
        $this->setShopUuid('');
        $this->setEmployeeId('');
        $this->setOwnerUuid('');
        $this->setOwnerEmail('');
    }

    /**
     * FIXME: to be refactored
     *
     * @param Dto\LinkShop $payload
     *
     * @return void
     *
     * @deprecated
     */
    public function update(Dto\LinkShop $payload)
    {
        $this->setShopUuid($payload->uid);
        $this->setEmployeeId((int) $payload->employeeId ?: '');
        $this->setOwnerUuid($payload->ownerUid);
        $this->setOwnerEmail($payload->ownerEmail);
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return (bool) $this->getShopUuid();
    }

    /**
     * @return true
     */
    public function isVerified()
    {
        // FIXME: define where this code belongs

        /** @var ShopStatus $shopStatus */
        $shopStatus = $this->commandBus->handle(new CheckStatusCommand());

        return $shopStatus->isVerified;
    }

    /**
     * @return string|null
     */
    public function linkedAt()
    {
        return $this->configuration->getShopUuidDateUpd();
    }

    /**
     * @return bool
     *
     * @throws \Exception
     *
     * @deprecated
     */
    public function existsV4()
    {
        return $this->configuration->getFirebaseIdToken()
            && !$this->configuration->getUserFirebaseIdToken()
            && $this->configuration->getFirebaseEmail();
    }

    /**
     * @return string
     */
    public function getShopUuid()
    {
        return $this->configuration->getShopUuid();
    }

    /**
     * @param string $uuid
     *
     * @return void
     */
    public function setShopUuid($uuid)
    {
        $this->configuration->updateShopUuid($uuid);
    }

    /**
     * @return int
     */
    public function getEmployeeId()
    {
        return (int) $this->configuration->getEmployeeId();
    }

    /**
     * @param int|string $employeeId
     *
     * @return void
     */
    public function setEmployeeId($employeeId)
    {
        $this->configuration->updateEmployeeId((string) $employeeId);
    }

    /**
     * @return string
     */
    public function getOwnerUuid()
    {
        return $this->configuration->getUserFirebaseUuid();
    }

    /**
     * @param string $uuid
     *
     * @return void
     */
    public function setOwnerUuid($uuid)
    {
        $this->configuration->updateUserFirebaseUuid((string) $uuid);
    }

    /**
     * @return string
     */
    public function getOwnerEmail()
    {
        return $this->configuration->getFirebaseEmail();
    }

    /**
     * @param string $email
     *
     * @return void
     */
    public function setOwnerEmail($email)
    {
        $this->configuration->updateFirebaseEmail($email);
    }
}