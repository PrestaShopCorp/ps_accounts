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
use PrestaShop\Module\PsAccounts\Account\ShopUrl;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2Session;
use PrestaShop\Module\PsAccounts\Adapter\Link as AccountsLink;
use PrestaShop\Module\PsAccounts\Installer\Installer;
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
     * @var string
     */
    private $alertCss = '
<style>
    .acc-flex
    {
        display: flex !important;
    }
    .acc-btn
    {
        display: inline-block !important;
        text-align: center !important;
        vertical-align: middle !important;
        user-select: none !important;
        border: 1px solid transparent !important;
        padding: .5rem 1rem !important;
        font-size: .875rem !important;
        line-height: 1.5 !important;
        font-weight: 600 !important;
        border-width: 1px !important;
        transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out !important;
        cursor: pointer !important;
    }
    .acc-btn-warning
    {
        width: max-content !important;
        color: #1d1d1b !important;
        background-color: #FFF5E5 !important;
        border-color: #ffb000 !important;
    }
    .acc-btn-warning:hover
    {
        background-color: #ffeccc !important;
    }
    .acc-btn-warning:focus, .acc-btn-warning.focus
    {
        background-color: #ffeccc !important;
    }
    .acc-btn-danger
    {
        width: max-content !important;
        color: #1d1d1b !important;
        background-color: #ffe4e6 !important;
        border-color: #ba151a !important;
    }
    .acc-btn-danger:hover
    {
        background-color: #fdbfbf !important;
    }
    .acc-btn-danger:focus, .acc-btn-danger.focus
    {
        background-color: #fdbfbf !important;
    }
    @media(max-width: 768px)
    {
        .acc-flex {
            flex-direction: column !important;
        }
        .acc-btn-warning,
        .acc-btn-danger
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
    .acc-alert
    {
    }
    .acc-alert-warning
    {
        background-color: #FFF5E5 !important;
        position: relative !important;
        padding: 16px 15px 16px 56px !important;
        font-size: 14px !important;
        border: solid 1px #ffb000 !important;
        color: #1d1d1b !important;
    }
    .acc-alert-danger
    {
        background-color: #ffe4e6 !important;
        position: relative !important;
        padding: 16px 15px 16px 56px !important;
        font-size: 14px !important;
        border: solid 1px #ba151a !important;
        color: #1d1d1b !important;
    }
</style>
';
    /**
     * @var string
     */
    private $translationClass;

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
        $this->translationClass = self::class;
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
            (string) json_encode($notifications ?: [])
        );
    }

    /**
     * @return array|array[]
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

        $shopId = $this->context->shop->id;
        $cloudShopUrl = ShopUrl::createFromStatus($status, $shopId)->trimmed();

        /** @var ShopProvider $shopProvider */
        $shopProvider = $this->module->getService(ShopProvider::class);
        $localShopUrl = $shopProvider->getUrl($shopId)->trimmed();

        try {
            if ($cloudShopUrl->frontendUrlEquals($localShopUrl)) {
                return [];
            }
        } catch (\InvalidArgumentException $e) {
            Logger::getInstance()->error($e->getMessage());

            return [];
        }

        /** @var AccountsLink $link */
        $link = $this->module->getService(AccountsLink::class);
        $moduleLink = $link->getAdminLink('AdminModules', true, [], [
            'configure' => 'ps_accounts',
        ]);

        return [[
            'html' => $this->alertCss . '
<div class="alert alert-warning acc-alert acc-alert-warning acc-flex">
    <div class="acc-flex-grow-1">
        <div class="acc-alert-title">
            ' . $this->module->l('Action required: confirm your store URL', $this->translationClass) . '
        </div>
        <p>
            ' . $this->module->l('We\'ve noticed that your store\'s URL no longer matches the one registered in your PrestaShop Account.', $this->translationClass) . '
            <br>
            ' . $this->module->l('For your services to function properly, you must either confirm this change or create a new identity for your store.', $this->translationClass) . '
        </p>
        <ul class="acc-list">
            <li>- ' . $this->module->l('Current store URL', $this->translationClass) . ': <em>' . $localShopUrl->getFrontendUrl() . '</em></li>
            <li>- ' . $this->module->l('URL registered in PrestaShop Account', $this->translationClass) . ': <em>' . $cloudShopUrl->getFrontendUrl() . '</em></li>
        </ul>
    </div>
    <div>
        <button class="btn warning btn-outline-warning acc-btn btn-warning acc-btn-warning" onclick="document.location=\'' . $moduleLink . '\'">
            ' . $this->module->l('Review settings', $this->translationClass) . '
        </button>
    </div>
</div>
',
        ]];
    }

    /**
     * @return array|array[]
     */
    protected function getNotificationsUpgradeFailed()
    {
        /** @var StatusManager $statusManager */
        $statusManager = $this->module->getService(StatusManager::class);

        /** @var UpgradeService $upgradeService */
        $upgradeService = $this->module->getService(UpgradeService::class);

        if ($upgradeService->getCoreRegisteredVersion() === \Ps_accounts::VERSION &&
            (!$statusManager->identityCreated() || $upgradeService->getRegisteredVersion() === \Ps_accounts::VERSION)) {
            return [];
        }

        /** @var AccountsLink $link */
        $link = $this->module->getService(AccountsLink::class);
        $resetLink = $link->getAdminLink('AdminAjaxPsAccounts', true, [], ['ajax' => 1, 'action' => 'resetModule']);

        return [[
            'html' => $this->alertCss . '
<div class="alert alert-danger acc-alert acc-alert-danger acc-flex">
    <div class="acc-flex-grow-1">
        <div class="acc-alert-title">
            ' . $this->module->l('Action required: reset your PS Account module', $this->translationClass) . '
        </div>
        <p>' . $this->module->l('A simple reset is needed to finish the update and ensure all your modules are working correctly.', $this->translationClass) . '</p>
    </div>
    <div>
        <button class="btn danger btn-outline-danger acc-btn btn-danger acc-btn-danger"
            onclick="this.disabled = true; this.innerHTML = \'' . $this->module->l('Resetting module...', $this->translationClass) . '\'; fetch(\'' . $resetLink . '\').then(response => {document.location.reload();})">
            ' . $this->module->l('Reset module', $this->translationClass) . '
        </button>
    </div>
</div>
',
        ]];
    }

    /**
     * @return void
     */
    public function ajaxProcessResetModule()
    {
        $status = false;
        try {
            /** @var Installer $installer */
            $installer = $this->module->getService(Installer::class);
            $status = $installer->resetModule('ps_accounts');
        } catch (\Exception $e) {
            Logger::getInstance()->error($e->getMessage());
        } catch (\Throwable $e) {
            Logger::getInstance()->error($e->getMessage());
        }
        $this->ajaxRender(
            (string) json_encode([
                'status' => $status,
            ])
        );
    }
}
