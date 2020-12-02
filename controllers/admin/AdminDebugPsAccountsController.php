<?php

use PrestaShop\Module\PrestashopCheckout\Adapter\LinkAdapter;
use PrestaShop\Module\PrestashopCheckout\ShopUuidManager;

/**
 * 2007-2020 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class AdminDebugPsAccountsController extends ModuleAdminController
{
    /**
     * AdminDebugController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->context = \Context::getContext();
    }

    public function initContent()
    {
        $this->context->smarty->assign(array(
            'config' => [
                'shopId' => (int)$this->context->shop->id,
                'moduleVersion' => \Ps_accounts::VERSION,
                'psVersion' => _PS_VERSION_,
                'phpVersion' => phpversion(),
                'firebase_email' => \Configuration::get('PS_ACCOUNTS_FIREBASE_EMAIL'),
                'firebase_email_is_verified' => \Configuration::get('PS_ACCOUNTS_FIREBASE_EMAIL_IS_VERIFIED'),
                'firebase_id_token' => \Configuration::get('PS_ACCOUNTS_FIREBASE_ID_TOKEN'),
                'firebase_refresh_token' => \Configuration::get('PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN'),
            ]
        ));
        $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . '/views/templates/admin/debug.tpl');
        parent::initContent();
    }
}
