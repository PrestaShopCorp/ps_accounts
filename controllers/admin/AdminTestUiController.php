<?php
/**
* 2007-2020 PrestaShop.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * Controller test ui
 */
class AdminTestUiController extends ModuleAdminController
{
    /**
     * Construct.
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * @return void
     */
    public function initContent()
    {
        parent::initContent();
        $this->context->smarty->assign([
            'pathApp' => Tools::getShopDomainSsl(true) . $this->module->getPath() . 'views/js/app.js',
            'pathVendor' => Tools::getShopDomainSsl(true) . $this->module->getPath() . 'views/js/chunk-vendors.js',
        ]);
        Media::addJsDef([
            'contextPsAccounts' => (new PrestaShop\AccountsAuth\Presenter\PsAccountsPresenter('ps_accounts'))->present(),
        ]);

        $this->setTemplate('configure.tpl');
    }
}
