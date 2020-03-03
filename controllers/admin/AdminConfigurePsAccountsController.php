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
use PrestaShop\Module\PsAccounts\Api\Firebase\Token;
use PrestaShop\Module\PsAccounts\Presenter\Store\StorePresenter;
use PrestaShop\Module\PsAccounts\Service\SshKey;

class AdminConfigurePsAccountsController extends ModuleAdminController
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
     * Initialize the content by adding Boostrap and loading the TPL.
     *
     * @param none
     *
     * @return none
     */
    public function initContent()
    {
        parent::initContent();

        $tplName = $this->dispatch();

        Media::addJsDef([
            'store' => (new StorePresenter($this->module, $this->context))->present(),
        ]);
        $this->context->smarty->assign([
            'pathApp' => Tools::getShopDomainSsl(true).$this->module->getPath().'views/js/app.js',
        ]);

        $this->setTemplate($tplName);
    }

    private function firstStepisDone()
    {
        return  Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY')
            && Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY')
            && Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA');
    }

    /**
     * @return string
     */
    private function dispatch()
    {
        if (!$this->context->employee->isSuperAdmin()) {
            return 'accessDenied.tpl';
        }

        if (
            $this->firstStepisDone()
        ) {
            if (isset($_GET['step']) && 4 == $_GET['step'] && isset($_GET['adminToken'])) {
                $this->getRefreshTokenWithAdminToken();

                return 'onboardingFinished.tpl';
            }
            $token = new Token();
            $token->refresh();

            if (!Configuration::get('PS_PSX_FIREBASE_REFRESH_TOKEN')) {
                return 'error.tpl';
            }

            return 'alreadyOnboarded.tpl';
        }

        if (Configuration::get('PS_PSX_FIREBASE_REFRESH_TOKEN')) {
            return 'accessDenied.tpl';
        }

        return 'configure.tpl';
    }

    private function getRefreshTokenWithAdminToken()
    {
        Configuration::updateValue('PS_PSX_FIREBASE_ADMIN_TOKEN', $_GET['adminToken']);
        $token = new Token();
        $token->getRefreshTokenWithAdminToken($_GET['adminToken']);
        $token->refresh();
    }

    private function manageSshKey()
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
                $data
            )
        );
    }
}
