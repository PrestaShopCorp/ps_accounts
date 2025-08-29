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
     *
     * FIXME: test reset on v9
     * FIXME: action=getNotifications
     * FIXME: fix reset not working (1785 5.6.2 -> 8)
     * FIXME: -> envoyer le vrai numéro de version
     * FIXME: -> reprise d'identité
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

        $cloudFrontendUrl = $status->frontendUrl;
        $localFrontendUrl = $shopUrl->getFrontendUrl();

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
<div class="alert alert-warning alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <strong>Warning!</strong> We detected a change in your shop URL.
    <br/>
    <ul>
        <li>PrestaShop Account URL&nbsp;: <em>' . $cloudFrontendUrl . '</em></li>
        <li>Your Shop URL&nbsp;: <em>' . $localFrontendUrl . '</em></li>
    </ul>
    Please review your <a href="' . $moduleLink . '">PrestaShop Account settings</a>
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
    <strong>Warning!</strong> PrestaShop Account module wasn\'t upgraded properly.
    <br />
    Please reset the module
</div>
',
        ];
    }
}
