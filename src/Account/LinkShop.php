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

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class LinkShop
{
    /**
     * @var RsaKeysProvider
     */
    private $rsaKeysProvider;

    /**
     * @var OwnerSession
     */
    private $ownerSession;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * ShopLinkAccountService constructor.
     *
     * @param RsaKeysProvider $rsaKeysProvider
     * @param ShopSession $shopSession
     * @param OwnerSession $ownerSession
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        RsaKeysProvider $rsaKeysProvider,
        ShopSession $shopSession,
        OwnerSession $ownerSession,
        ConfigurationRepository $configuration
    ) {
        $this->rsaKeysProvider = $rsaKeysProvider;
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
        $this->configuration = $configuration;
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function delete()
    {
        $this->shopSession->cleanup();
        $this->ownerSession->cleanup();
        $this->setEmployeeId(null);

        try {
            $this->rsaKeysProvider->cleanupKeys();
            $this->rsaKeysProvider->generateKeys();
        } catch (\Exception $e) {
        }
    }

    /**
     * @param Dto\LinkShop $payload
     *
     * @return void
     */
    public function update(Dto\LinkShop $payload)
    {
        //$this->shopSession->setToken($payload->shopToken, $payload->shopRefreshToken);
        //$this->ownerSession->setToken($payload->userToken, $payload->userRefreshToken);
        $this->setEmployeeId((int) $payload->employeeId ?: null);
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function exists()
    {
        return (bool) $this->getShopUuid();
//        return !($this->shopSession->getOrRefreshToken()->getJwt() instanceof NullToken)
//            && !($this->ownerSession->getOrRefreshToken()->getJwt() instanceof NullToken);
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
        return !($this->shopSession->getOrRefreshToken()->getJwt() instanceof NullToken)
            && ($this->ownerSession->getOrRefreshToken()->getJwt() instanceof NullToken)
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
}
