<?php

use PrestaShop\AccountsAuth\DependencyInjection\PsAccountsServiceProvider;
use PrestaShop\AccountsAuth\Repository\ConfigurationRepository;

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
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * AdminDebugController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $serviceProvider = PsAccountsServiceProvider::getInstance();

        $this->context = $serviceProvider->get(\Context::class);
        $this->configuration = $serviceProvider->get(ConfigurationRepository::class);
    }

    /**
     * @return void
     *
     * @throws SmartyException
     */
    public function initContent()
    {
        $this->context->smarty->assign([
            'config' => [
                'shopId' => (int) $this->context->shop->id,
                'shopUuidV4' => $this->configuration->getShopUuid(),
                'moduleVersion' => \Ps_accounts::VERSION,
                'psVersion' => _PS_VERSION_,
                'phpVersion' => phpversion(),
                'firebase_email' => $this->configuration->getFirebaseEmail(),
                'firebase_email_is_verified' => $this->configuration->firebaseEmailIsVerified(),
                'firebase_id_token' => $this->configuration->getFirebaseIdToken(),
                'firebase_refresh_token' => $this->configuration->getFirebaseRefreshToken(),
                'unlinkShopUrl' => 'index.php?controller=AdminAjaxPsAccounts&ajax=1&action=unlinkShop&token=' . Tools::getAdminTokenLite('AdminAjaxPsAccounts'),
                'isShopLinked' => $this->isAccountLinked(),
            ],
        ]);
        $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . '/views/templates/admin/debug.tpl');
        parent::initContent();
    }

    /**
     * @return bool
     */
    public function isAccountLinked()
    {
        return $this->configuration->getFirebaseIdToken()
            && $this->configuration->getFirebaseEmail()
            && $this->configuration->firebaseEmailIsVerified();
    }
}
