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

namespace PrestaShop\Module\PsAccounts\Middleware;

use Exception;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopLogoutTrait;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Ps_accounts;

class Oauth2Middleware
{
    use PrestaShopLogoutTrait;

    /**
     * @var Ps_accounts
     */
    private $module;

    /**
     * @var bool
     */
    private $bypassLoginPage = false;

    public function __construct(Ps_accounts $ps_accounts)
    {
        $this->module = $ps_accounts;
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            /** @var PsAccountsService $psAccountsService */
            $psAccountsService = $this->module->getService(PsAccountsService::class);

            $session = $this->getOauth2Session();

            if (isset($_GET['logout'])) {
                if ($psAccountsService->getLoginActivated()) {
                    $this->oauth2Logout();
                    // FIXME: too much implicit logic here
                    // We reach this line after redirect at callback time
                    $this->onLogoutCallback();
                } else {
                    $session->clear();
                }
            } else {
                // We keep token fresh !
                $session->getOrRefreshAccessToken();
            }
        } catch (IdentityProviderException $e) {
            $this->module->getLogger()->err($e->getMessage());
        } catch (Exception $e) {
            $this->module->getLogger()->err($e->getMessage());
        }
    }

    /**
     * @return ShopProvider
     *
     * @throws Exception
     */
    protected function getProvider()
    {
        return $this->module->getService(ShopProvider::class);
    }

    /**
     * @return PrestaShopSession
     *
     * @throws Exception
     */
    protected function getOauth2Session()
    {
        return $this->module->getService(PrestaShopSession::class);
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    protected function isOauth2LogoutEnabled()
    {
        // return $this->module->hasParameter('ps_accounts.oauth2_url_session_logout');
        // FIXME
        return true;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    protected function onLogoutCallback()
    {
        if ($this->bypassLoginPage) {
            \Tools::redirectLink($this->getProvider()->getRedirectUri());
        }
    }
}
