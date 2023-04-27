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

use PrestaShop\Module\PsAccounts\Handler\Error\Sentry;
use PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

/**
 * Controller for all ajax calls.
 */
class AdminAjaxPsAccountsController extends ModuleAdminController
{
    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * AdminAjaxPsAccountsController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetOrRefreshToken()
    {
        try {
            /** @var ShopTokenRepository $shopTokenService */
            $shopTokenService = $this->module->getService(ShopTokenRepository::class);

            header('Content-Type: text/json');

            $this->ajaxDie(
                json_encode([
                    'token' => (string) $shopTokenService->getOrRefreshToken(),
                    'refreshToken' => $shopTokenService->getRefreshToken(),
                ])
            );
        } catch (Exception $e) {
            Sentry::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    //public function displayAjaxUnlinkShop()
    public function ajaxProcessUnlinkShop()
    {
        try {
            /** @var ShopLinkAccountService $shopLinkAccountService */
            $shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

            $response = $shopLinkAccountService->unlinkShop();

            http_response_code($response['httpCode']);

            header('Content-Type: text/json');

            $this->ajaxDie(json_encode($response['body']));
        } catch (Exception $e) {
            Sentry::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessResetLinkAccount()
    {
        try {
            /** @var ShopLinkAccountService $shopLinkAccountService */
            $shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

            $shopLinkAccountService->resetLinkAccount();

            header('Content-Type: text/json');

            $this->ajaxDie(json_encode(['message' => 'success']));
        } catch (Exception $e) {
            Sentry::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetContext()
    {
        try {
            $psxName = Tools::getValue('psx_name');

            /** @var PsAccountsPresenter $presenter */
            $presenter = $this->module->getService(PsAccountsPresenter::class);

            header('Content-Type: text/json');

            $this->ajaxDie(json_encode($presenter->present($psxName)));
        } catch (Exception $e) {
            Sentry::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetOrRefreshAccessToken()
    {
        try {
            /** @var PrestaShopSession $oauthSession */
            $oauthSession = $this->module->getService(PrestaShopSession::class);

            header('Content-Type: text/json');

            $this->ajaxDie(
                json_encode([
                    'token' => (string) $oauthSession->getOrRefreshAccessToken(),
                ])
            );
        } catch (Exception $e) {
            Sentry::captureAndRethrow($e);
        }
    }
}
