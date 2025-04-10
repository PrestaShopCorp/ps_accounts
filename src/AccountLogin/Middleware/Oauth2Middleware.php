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

namespace PrestaShop\Module\PsAccounts\AccountLogin\Middleware;

use Exception;
use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2LogoutTrait;
use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2Session;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use Ps_accounts;

class Oauth2Middleware
{
    use OAuth2LogoutTrait;

    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * @var OAuth2Session
     */
    private $oAuth2Session;

    /**
     * @var OAuth2Service
     */
    private $oAuth2Service;

    public function __construct(Ps_accounts $module)
    {
        $this->psAccountsService = $module->getService(PsAccountsService::class);
        $this->oAuth2Session = $module->getService(OAuth2Session::class);
        $this->oAuth2Service = $module->getService(OAuth2Service::class);
    }

    /**
     * @return void
     *
     * @deprecated since v7.1.2
     */
    public function execute()
    {
        try {
            if (isset($_GET['logout'])) {
                $this->executeLogout();
            }
        } catch (Exception $e) {
            Logger::getInstance()->err($e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function handleLogout()
    {
        try {
            if (isset($_GET['logout'])) {
                $this->executeLogout();
            }
        } catch (Exception $e) {
            Logger::getInstance()->err($e->getMessage());
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function executeLogout()
    {
        if ($this->psAccountsService->getLoginActivated() &&
            !isset($_GET[OAuth2Client::getQueryLogoutCallbackParam()])) {
            $this->oauth2Logout();
        }
        $this->getOauth2Session()->clear();
    }

    /**
     * @return OAuth2Service
     */
    protected function getOAuth2Service()
    {
        return $this->oAuth2Service;
    }

    /**
     * @return OAuth2Session
     *
     * @throws Exception
     */
    protected function getOauth2Session()
    {
        return $this->oAuth2Session;
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
}
