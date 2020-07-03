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

use PrestaShop\AccountsAuth\Presenter\PsAccountsPresenter;

/**
 * Controller reset onboarding.
 */
class AdminResetOnboardingController extends ModuleAdminController
{
    /**
     * @return void
     */
    public function initContent()
    {
        $psAccountsPresenter = new PsAccountsPresenter('');
        $shopId = $psAccountsPresenter->getCurrentShop()['id'];
        Configuration::updateValue('PS_ACCOUNTS_RSA_PRIVATE_KEY', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_ACCOUNTS_RSA_PUBLIC_KEY', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_ACCOUNTS_RSA_SIGN_DATA', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_PSX_FIREBASE_EMAIL', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_PSX_FIREBASE_ID_TOKEN', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_PSX_FIREBASE_LOCAL_ID', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_PSX_FIREBASE_REFRESH_TOKEN', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_PSX_FIREBASE_REFRESH_DATE', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_PSX_FIREBASE_ADMIN_TOKEN', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_PSX_FIREBASE_LOCK', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_PSX_EMAIL', null, false, null, (int) $shopId);
        Configuration::updateValue('PS_PSX_EMAIL_IS_VERIFIED', null, false, null, (int) $shopId);
        Configuration::updateValue('PSX_UUID_V4', null, false, null, (int) $shopId);

        $this->ajaxDie(
            json_encode(true)
        );
    }
}
