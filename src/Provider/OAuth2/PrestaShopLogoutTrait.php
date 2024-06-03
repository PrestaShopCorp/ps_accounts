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

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

trait PrestaShopLogoutTrait
{
    /**
     * @return ShopProvider
     */
    abstract protected function getProvider();

    /**
     * @return PrestaShopSession
     */
    abstract protected function getOauth2Session();

    /**
     * @return bool
     */
    abstract protected function isOauth2LogoutEnabled();

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function oauth2Logout()
    {
        if (!$this->isOauth2LogoutEnabled()) {
            return;
        }

        $oauth2Session = $this->getOauth2Session();
        if (!isset($_GET[ShopProvider::QUERY_LOGOUT_CALLBACK_PARAM])) {
            $idToken = $oauth2Session->getIdToken();

            if (empty($idToken)) {
                return;
            }

            $logoutUrl = $this->getProvider()->getLogoutUrl([
                'id_token_hint' => $idToken,
            ]);

            header('Location: ' . $logoutUrl);
            exit;
        } else {
            $oauth2Session->clear();
        }
    }
}
