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
require_once __DIR__ . '/../../src/Polyfill/Traits/Controller/AjaxRender.php';

use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2Session;
use PrestaShop\Module\PsAccounts\Adapter\Link as AccountsLink;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\Controller\AjaxRender;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\SentryService;
use PrestaShop\Module\PsAccounts\Service\UpgradeService;

/**
 * Controller for all ajax calls.
 */
class AdminAjaxPsAccountsController extends \ModuleAdminController
{
    use AjaxRender;

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

        $this->ajax = true;
        $this->content_only = true;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetOrRefreshToken()
    {
        try {
            /** @var ShopSession $shopSession */
            $shopSession = $this->module->getService(ShopSession::class);

            header('Content-Type: text/json');

            $token = $shopSession->getValidToken();

            $this->ajaxRender(
                (string) json_encode([
                    'token' => (string) $token->getJwt(),
                    'refreshToken' => $token->getRefreshToken(),
                ])
            );
        } catch (Exception $e) {
            SentryService::captureAndRethrow($e);
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
            /** @var OAuth2Session $oauth2Session */
            $oauth2Session = $this->module->getService(OAuth2Session::class);

            header('Content-Type: text/json');

            $this->ajaxRender(
                (string) json_encode([
                    'token' => (string) $oauth2Session->getOrRefreshAccessToken(),
                ])
            );
        } catch (Exception $e) {
            SentryService::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     */
    public function ajaxProcessGetNotifications()
    {
        $notifications = [];
        try {
            $notifications = array_merge(
                $this->getNotificationsUpgradeFailed(),
                $this->getNotificationsUrlMismatch()
            );
        } catch (\Exception $e) {
            Logger::getInstance()->error($e->getMessage());
        } catch (\Throwable $e) {
            Logger::getInstance()->error($e->getMessage());
        }
        $this->ajaxRender(
            (string) json_encode($notifications ? [$notifications] : [])
        );
    }

    /**
     * @return array|string[]
     *
     * @throws UnknownStatusException
     */
    protected function getNotificationsUrlMismatch()
    {
        /** @var StatusManager $statusManager */
        $statusManager = $this->module->getService(StatusManager::class);

        if (!$statusManager->identityCreated()) {
            return [];
        }

        $status = $statusManager->getStatus();

        /** @var ShopProvider $shopProvider */
        $shopProvider = $this->module->getService(ShopProvider::class);
        $shopUrl = $shopProvider->getUrl($this->context->shop->id);

        $cloudFrontendUrl = rtrim($status->frontendUrl, '/');
        $localFrontendUrl = rtrim($shopUrl->getFrontendUrl(), '/');

        if ($localFrontendUrl === $cloudFrontendUrl) {
            return [];
        }

        /** @var AccountsLink $link */
        $link = $this->module->getService(AccountsLink::class);
        $moduleLink = $link->getAdminLink('AdminModules', true, [], [
            'configure' => 'ps_accounts',
        ]);

        return [
            'html' => '
<style>
    .acc-flex
    {
        display: flex !important;
    }
    .acc-btn-warning
    {
        width: max-content !important;
    }
    @media(max-width: 768px)
    {
        .acc-flex {
            flex-direction: column !important;
        }
        .acc-btn-warning
        {
            margin-top: 1em !important;
        }
    }
    .acc-flex-grow-1
    {
        -webkit-box-flex: 1 !important;
        -ms-flex-positive: 1 !important;
        flex-grow: 1 !important;
    }
    .acc-alert-title
    {
        font-weight: bold !important;
        margin-bottom: .9375rem !important;
    }
    .acc-list
    {
        list-style-type: none;
        padding-left: 0 !important;
    }
</style>
<div class="alert alert-warning acc-flex">
    <div class="acc-flex-grow-1">
        <div class="acc-alert-title">
            ' . $this->module->l('Action required: confirm your store URL') . '
        </div>
        <p>' . $this->module->l('We\'ve noticed that your store\'s URL no longer matches the one registered in your PrestaShop Account. For your services to function properly, you must either confirm this change or create a new identity for your store.') . '</p>
        <ul class="acc-list">
            <li>- ' . $this->module->l('Current store URL') . ': <a target="_blank" href="' . $localFrontendUrl . '">' . $localFrontendUrl . '</a></li>
            <li>- ' . $this->module->l('URL registered in PrestaShop Account') . ': <a target="_blank" href="' . $cloudFrontendUrl . '">' . $cloudFrontendUrl . '</a></li>
        </ul>
    </div>
    <div>
        <button class="btn btn-outline-warning btn-warning acc-btn-warning" onclick="document.location=\'' . $moduleLink . '\'">
            ' . $this->module->l('Review settings') . '
        </button>
    </div>
</div>

',
        ];
    }

    /**
     * @return array|string[]
     */
    protected function getNotificationsUpgradeFailed()
    {
        /** @var UpgradeService $upgradeService */
        $upgradeService = $this->module->getService(UpgradeService::class);

        if ($upgradeService->getCoreRegisteredVersion() === \Ps_accounts::VERSION) {
            return [];
        }

        return [
            'html' => '
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <strong>' . $this->module->l('Warning!') . '</strong> ' . $this->module->l('PrestaShop Account module wasn\'t upgraded properly.') . '
    <br />
    ' . $this->module->l('Please reset the module') . '
</div>
',
        ];
    }
}
