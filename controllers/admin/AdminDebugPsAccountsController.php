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

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

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
     * @var Ps_accounts
     */
    public $module;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * AdminDebugController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = $this->module->getService(ConfigurationRepository::class);
        $this->psAccountsService = $this->module->getService(PsAccountsService::class);
    }

    /**
     * @return void
     *
     * @throws SmartyException
     * @throws Exception
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
                'isShopLinked' => $this->psAccountsService->isAccountLinked(),
            ],
        ]);
        $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . '/views/templates/admin/debug.tpl');
        parent::initContent();
    }
}
