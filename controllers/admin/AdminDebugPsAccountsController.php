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

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

class AdminDebugPsAccountsController extends \ModuleAdminController
{
    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * @return void
     *
     * @throws SmartyException
     * @throws Exception
     */
    public function initContent()
    {
        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

        /** @var ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);

        /** @var PsAccountsService $psAccountsService */
        $psAccountsService = $this->module->getService(PsAccountsService::class);

        /** @var LinkShop $linkShop */
        $linkShop = $this->module->getService(LinkShop::class);

        $this->context->smarty->assign([
            'config' => [
                'shopId' => (int) $this->context->shop->id,
                'shopUuidV4' => $linkShop->getShopUuid(),
                'moduleVersion' => \Ps_accounts::VERSION,
                'psVersion' => _PS_VERSION_,
                'phpVersion' => phpversion(),
                'firebase_email' => $ownerSession->getToken()->getEmail(),
                'firebase_email_is_verified' => $ownerSession->isEmailVerified(),
                'firebase_id_token' => (string) $shopSession->getToken(),
                'firebase_refresh_token' => '',
                'adminAjaxUrl' => $psAccountsService->getAdminAjaxUrl(),
                'isShopLinked' => $psAccountsService->isAccountLinked(),
            ],
        ]);
        $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . '/views/templates/admin/debug.tpl');
        parent::initContent();
    }
}
