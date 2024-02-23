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

        $this->errors = array();

        $this->display_header = false;
        $this->display_footer = false;

        /** @var Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        $this->psAccounts = $module;

        if (!headers_sent()) {
            header('Login: true');
        }
    }

    public function initContent()
    {
//        if (file_exists(_PS_ADMIN_DIR_ . '/../install')) {
//            $this->context->smarty->assign('wrong_install_name', true);
//        }
//
//        // Redirect to admin panel
//        if (Tools::isSubmit('redirect') && Validate::isControllerName(Tools::getValue('redirect'))) {
//            $this->context->smarty->assign('redirect', Tools::getValue('redirect'));
//        } else {
//            $tab = new Tab((int) $this->context->employee->default_tab);
//            $this->context->smarty->assign('redirect', $this->context->link->getAdminLink($tab->class_name));
//        }
//
//        if ($nb_errors = count($this->errors)) {
//            $this->context->smarty->assign(array(
//                'errors' => $this->errors,
//                'nbErrors' => $nb_errors,
//                'shop_name' => Tools::safeOutput(Configuration::get('PS_SHOP_NAME')),
//                'disableDefaultErrorOutPut' => true,
//            ));
//        }

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
        $this->context->smarty->assign('shopUrl', $this->context->shop->getBaseUrl(true));

        $this->context->smarty->assign('oauthRedirectUri', $provider->getRedirectUri());
        $this->context->smarty->assign('legacyLoginUri', $this->context->link->getAdminLink('AdminLogin', true, [], [
            'mode' => self::PARAM_MODE_LOCAL,
        ]));

        /* @phpstan-ignore-next-line */
        $isoCode = $this->context->currentLocale->getCode();

        $this->context->smarty->assign('isoCode', substr($isoCode, 0, 2));
        $this->context->smarty->assign('defaultIsoCode', 'en');
        $this->context->smarty->assign('testimonials', $testimonials);

        $this->context->smarty->assign('loginError', $session->remove('loginError'));
        $this->context->smarty->assign('meta_title', '');
        $this->context->smarty->assign('ssoResendVerificationEmail',
            $this->psAccounts->getParameter('ps_accounts.sso_resend_verification_email_url')
        );

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
            file_get_contents(
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
