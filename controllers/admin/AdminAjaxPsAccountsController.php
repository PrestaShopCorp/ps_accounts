<?php
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
use PrestaShop\AccountsAuth\Service\SshKey;

/**
 * Controller for all call ajax.
 */
class AdminAjaxPsAccountsController extends ModuleAdminController
{
    const STR_TO_SIGN = 'data';

    /**
     * AJAX: Generate ssh key.
     *
     * @return void
     */
    public function ajaxProcessGenerateSshKey()
    {
        $sshKey = new SshKey();
        $key = $sshKey->generate();
        Configuration::updateValue('PS_ACCOUNTS_RSA_PRIVATE_KEY', $key['privatekey']);
        Configuration::updateValue('PS_ACCOUNTS_RSA_PUBLIC_KEY', $key['publickey']);
        $data = 'data';
        Configuration::updateValue(
            'PS_ACCOUNTS_RSA_SIGN_DATA',
            $sshKey->signData(
                Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY'),
                self::STR_TO_SIGN
            )
        );

        $this->ajaxDie(
            json_encode(Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY'))
        );
    }

    /**
     * AJAX: Save Admin Token.
     *
     * @return void
     */
    public function ajaxProcessSaveAdminToken()
    {
        Configuration::updateValue('PS_PSX_FIREBASE_ADMIN_TOKEN', Tools::getValue('adminToken'));

        $this->ajaxDie(
            json_encode(true)
        );
    }
}
