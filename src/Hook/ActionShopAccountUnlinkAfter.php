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

namespace PrestaShop\Module\PsAccounts\Hook;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ActionShopAccountUnlinkAfter extends Hook
{
    /**
     * @param array $params
     *
     * @return void
     *
     * @throws \Exception
     */
    public function execute(array $params = [])
    {
        /** @var Oauth2Client $oauth2Client */
        $oauth2Client = $this->ps_accounts->getService(Oauth2Client::class);
        $oauth2Client->delete();

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->ps_accounts->getService(ConfigurationRepository::class);
        $configuration->updateLoginEnabled(false);

        /** @var ShopSession $shopSession */
        $shopSession = $this->ps_accounts->getService(ShopSession::class);
        $shopSession->cleanup();

        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->ps_accounts->getService(OwnerSession::class);
        $ownerSession->cleanup();

        /** @var \PrestaShop\Module\PsAccounts\Account\Session\ShopSession $session */
        $session = $this->ps_accounts->getService(\PrestaShop\Module\PsAccounts\Account\Session\ShopSession::class);
        $session->cleanup();

        /** @var RsaKeysProvider $rsaKeysProvider */
        $rsaKeysProvider = $this->ps_accounts->getService(RsaKeysProvider::class);
        try {
            $rsaKeysProvider->cleanupKeys();
            $rsaKeysProvider->generateKeys();
        } catch (\Exception $e) {
        }
    }
}
