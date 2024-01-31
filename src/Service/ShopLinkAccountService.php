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

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Account\Session\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ShopLinkAccountService
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
    private $configurationRepository;

    /**
     * ShopLinkAccountService constructor.
     *
     * @param RsaKeysProvider $rsaKeysProvider
     * @param ShopSession $shopSession
     * @param OwnerSession $ownerSession
     * @param ConfigurationRepository $configurationRepository
     */
    public function __construct(
        RsaKeysProvider $rsaKeysProvider,
        ShopSession $shopSession,
        OwnerSession $ownerSession,
        ConfigurationRepository $configurationRepository
    ) {
        $this->rsaKeysProvider = $rsaKeysProvider;
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * Empty onboarding configuration values
     *
     * @return void
     *
     * @throws \Exception
     */
    public function resetLinkAccount()
    {
        $this->rsaKeysProvider->cleanupKeys();
        $this->shopSession->cleanup();
        $this->ownerSession->cleanup();
        try {
            $this->rsaKeysProvider->generateKeys();
        } catch (\Exception $e) {
        }

        // TODO: on unlink reaction
        //$this->configuration->updateLoginEnabled(false);
        //$this->oauth2Client->delete();
        $this->configurationRepository->updateShopUnlinkedAuto(false);
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isAccountLinked()
    {
        return !($this->shopSession->getOrRefreshToken()->getJwt() instanceof NullToken)
            && !($this->ownerSession->getOrRefreshToken()->getJwt() instanceof NullToken);
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isAccountLinkedV4()
    {
        return !($this->shopSession->getOrRefreshToken()->getJwt() instanceof NullToken)
            && ($this->ownerSession->getOrRefreshToken()->getJwt() instanceof NullToken)
            && $this->configurationRepository->getFirebaseEmail();
    }

    /**
     * @return OwnerSession
     */
    public function getOwnerSession()
    {
        return $this->ownerSession;
    }

    /**
     * @return ShopSession
     */
    public function getShopSession()
    {
        return $this->shopSession;
    }
}
