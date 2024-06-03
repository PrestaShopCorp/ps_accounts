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

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class LinkShop
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * ShopLinkAccountService constructor.
     *
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        ConfigurationRepository $configuration
    ) {
        $this->configuration = $configuration;
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function delete()
    {
        $this->setShopUuid(null);
        $this->setEmployeeId(null);
        $this->setOwnerUuid(null);
        $this->setOwnerEmail(null);
    }

    /**
     * @param Dto\LinkShop $payload
     *
     * @return void
     */
    public function update(Dto\LinkShop $payload)
    {
        $this->setShopUuid($payload->uid);
        $this->setEmployeeId((int) $payload->employeeId ?: null);
        $this->setOwnerUuid($payload->ownerUid);
        $this->setOwnerEmail($payload->ownerEmail);
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function exists()
    {
        return (bool) $this->getShopUuid();
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
     * @param string|null $uuid
     *
     * @return void
     */
    public function setShopUuid($uuid)
    {
        $this->configuration->updateShopUuid($uuid);
    }

    /**
     * @return int|null
     */
    public function getEmployeeId()
    {
        return (int) $this->configuration->getEmployeeId();
    }

    /**
     * @param int|null $employeeId
     *
     * @return void
     */
    public function setEmployeeId($employeeId)
    {
        $this->configuration->updateEmployeeId((string) $employeeId);
    }

    /**
     * @return int
     */
    public function getOwnerUuid()
    {
        return $this->configuration->getUserFirebaseUuid();
    }

    /**
     * @param string|null $uuid
     *
     * @return void
     */
    public function setOwnerUuid($uuid)
    {
        $this->configuration->updateUserFirebaseUuid((string) $uuid);
    }

    /**
     * @return string|null
     */
    public function getOwnerEmail()
    {
        return $this->configuration->getFirebaseEmail();
    }

    /**
     * @param string|null $email
     *
     * @return void
     */
    public function setOwnerEmail($email)
    {
        $this->configuration->updateFirebaseEmail($email);
    }
}
