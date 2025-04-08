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

use PrestaShop\Module\PsAccounts\Api\Client\ExternalAssetsClient;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\AdminController\IsAnonymousAllowed;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;

class AdminLoginPsAccountsController extends \AdminController
{
    use IsAnonymousAllowed;

    const PARAM_MODE_LOCAL = 'local';

    /**
     * @var string
     */
    public $template = 'login.tpl';

    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * @var ExternalAssetsClient
     */
    private $externalAssetsClient;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();

        $this->errors = [];

        $this->display_header = false;
        /* @phpstan-ignore-next-line */
        $this->display_footer = false;

        /** @var Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');
        $this->module = $module;

        $this->externalAssetsClient = $this->module->getService(ExternalAssetsClient::class);

        if (!headers_sent()) {
            header('Login: true');
        }
    }

    /**
     * @return void
     */
    public function initContent()
    {
        if ($nb_errors = count($this->errors)) {
            $this->context->smarty->assign([
                'errors' => $this->errors,
                'nbErrors' => $nb_errors,
                'shop_name' => Tools::safeOutput((string) Configuration::get('PS_SHOP_NAME')),
                'disableDefaultErrorOutPut' => true,
            ]);
        }

        $this->setMedia($isNewTheme = false);
        $this->initHeader();
        parent::initContent();
        $this->initFooter();

        //force to disable modals
        $this->context->smarty->assign('modals', null);
    }

    /**
     * @return bool
     */
    public function checkToken()
    {
        return true;
    }

    /**
     * All BO users can access the login page
     *
     * @param bool $disable
     *
     * @return bool
     */
    public function viewAccess($disable = false)
    {
        return true;
    }

    /**
     * @param bool $isNewTheme
     *
     * @return void
     */
    public function setMedia($isNewTheme = false)
    {
        $this->addCss($this->module->getLocalPath() . '/views/css/login.css');
        $this->addJS($this->module->getLocalPath() . '/views/js/login.js');
    }

    /**
     * @param string $tpl_name
     *
     * @phpstan-ignore-next-line
     *
     * @return Smarty_Internal_Template
     */
    public function createTemplate($tpl_name)
    {
        /** @var Oauth2Client $oAuth2Client */
        $oAuth2Client = $this->module->getService(Oauth2Client::class);

        $session = $this->module->getSession();

        /* @phpstan-ignore-next-line */
        $isoCode = $this->context->currentLocale->getCode();

        $this->context->smarty->assign([
            /* @phpstan-ignore-next-line */
            'shopUrl' => $this->context->shop->getBaseUrl(true),
            'oauthRedirectUri' => $oAuth2Client->getRedirectUri(),
            'legacyLoginUri' => $this->context->link->getAdminLink(
                'AdminLogin', true, [], [
                'mode' => self::PARAM_MODE_LOCAL,
            ]),
            'isoCode' => substr($isoCode, 0, 2),
            'defaultIsoCode' => 'en',
            'testimonials' => $this->getTestimonials(),
            'loginError' => $session->remove('loginError'),
            'meta_title' => '',
            'ssoResendVerificationEmail' => $this->module->getParameter(
                'ps_accounts.sso_resend_verification_email_url'
            ),
        ]);

        /* @phpstan-ignore-next-line */
        return $this->context->smarty->createTemplate(
            $this->module->getLocalPath() . '/views/templates/admin/' . $this->template,
            $this->context->smarty
        );
    }

    /**
     * @return array
     */
    private function getTestimonials()
    {
        $res = $this->externalAssetsClient->getTestimonials();

        return $res['status'] ? $res['body'] : [];
    }
}
