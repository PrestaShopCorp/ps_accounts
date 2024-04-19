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

use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;

class AdminLoginPsAccountsController extends \AdminController
{
    const PARAM_MODE_LOCAL = 'local';

    /** @var string */
    public $template = 'login.tpl';

    /** @var Ps_accounts */
    private $psAccounts;

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

        $this->psAccounts = $module;

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
    protected function isAnonymousAllowed()
    {
        return true;
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
        $this->addCss($this->psAccounts->getLocalPath() . '/views/css/login.css');
        $this->addJS($this->psAccounts->getLocalPath() . '/views/js/login.js');
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
        /** @var ShopProvider $provider */
        $provider = $this->psAccounts->getService(ShopProvider::class);

        $testimonials = $this->getTestimonials();

        $session = $this->psAccounts->getSession();

        /* @phpstan-ignore-next-line */
        $isoCode = $this->context->currentLocale->getCode();

        $this->context->smarty->assign([
            /* @phpstan-ignore-next-line */
            'shopUrl' => $this->context->shop->getBaseUrl(true),
            'oauthRedirectUri' => $provider->getRedirectUri(),
            'legacyLoginUri' => $this->context->link->getAdminLink(
                'AdminLogin', true, [], [
                'mode' => self::PARAM_MODE_LOCAL,
            ]),
            'isoCode' => substr($isoCode, 0, 2),
            'defaultIsoCode' => 'en',
            'testimonials' => $testimonials,
            'loginError' => $session->remove('loginError'),
            'meta_title' => '',
            'ssoResendVerificationEmail' => $this->psAccounts->getParameter(
                'ps_accounts.sso_resend_verification_email_url'
            ),
        ]);

        /* @phpstan-ignore-next-line */
        return $this->context->smarty->createTemplate(
            $this->psAccounts->getLocalPath() . '/views/templates/admin/' . $this->template,
            $this->context->smarty
        );
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    private function getTestimonials()
    {
        $verify = (bool) $this->psAccounts->getParameter('ps_accounts.check_api_ssl_cert');

        return json_decode(
            (string) Tools::file_get_contents(
                $this->psAccounts->getParameter('ps_accounts.testimonials_url'),
                false,
                stream_context_create([
                    'ssl' => [
                        'verify_peer' => $verify,
                        'verify_peer_name' => $verify,
                    ],
                    'http' => [
                        'ignore_errors' => '1',
                    ],
                ])
            ),
            true
        ) ?: [];
    }
}
