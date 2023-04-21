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

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class Account
{
    /**
     * @var OwnerSession
     */
    private $ownerSession;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var PublicKey
     */
    private $publicKey;

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    public function __construct(
        PublicKey $publicKey,
        ShopSession $shopSession,
        OwnerSession $ownerSession,
        ConfigurationRepository $configurationRepository
    ) {
        $this->publicKey = $publicKey;
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * Empty onboarding configuration values
     *
     * @return void
     */
    public function resetLink(): void
    {
        $this->publicKey->cleanupKeys();
        $this->shopSession->cleanup();
        $this->ownerSession->cleanup();
        $this->configurationRepository->updateLoginEnabled(false);
        try {
            $this->publicKey->generateKeys();
        } catch (\Exception $e) {
        }
    }

    /**
     * @throws \Throwable
     */
    public function isLinked(): bool
    {
        return !($this->shopSession->getOrRefreshToken()->getToken() instanceof NullToken)
            && !($this->ownerSession->getOrRefreshToken()->getToken() instanceof NullToken);
    }

    /**
     * @throws \Throwable
     */
    public function isLinkedV4(): bool
    {
        return !($this->shopSession->getOrRefreshToken()->getToken() instanceof NullToken)
            && ($this->ownerSession->getOrRefreshToken()->getToken() instanceof NullToken)
            && $this->configurationRepository->getFirebaseEmail();
    }

    /**
     * @return OwnerSession
     */
    public function getOwnerSession(): OwnerSession
    {
        return $this->ownerSession;
    }

    /**
     * @return ShopSession
     */
    public function getShopSession(): ShopSession
    {
        return $this->shopSession;
    }
}
