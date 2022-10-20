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

use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

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
class AdminLoginController extends AdminLoginControllerCore
{
    const PS_ACCOUNTS_LOGIN_MODE_LOCAL = 'local';

    /** @var string */
    public $template = 'content.tpl';

    /** @var bool */
    private $psAccountsLoginEnabled = false;

    /** @var Ps_accounts */
    private $psAccountsModule;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        /** @var Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        $this->psAccountsModule = $module;

        /** @var PsAccountsService $moduleService */
        $moduleService = $this->psAccountsModule->getService(PsAccountsService::class);

        if (self::PS_ACCOUNTS_LOGIN_MODE_LOCAL !== $this->getPsAccountsLoginMode()) {
            $this->psAccountsLoginEnabled = $moduleService->getLoginActivated();
        }
    }

    /* @phpstan-ignore-next-line */
    public function setMedia($isNewTheme = false)
    {
        if ($this->psAccountsLoginEnabled) {
            $this->addCss(_PS_MODULE_DIR_ . 'ps_accounts/views/css/login.css');
            $this->addJS(_PS_MODULE_DIR_ . '/ps_accounts/views/js/login.js');

            return;
        }

        parent::setMedia();
    }

    /**
     * Create a template from the override file, else from the base file.
     *
     * @param string $tpl_name filename
     *
     * @return Smarty_Internal_Template
     *
     * @throws SmartyException
     */
    public function createTemplate($tpl_name)
    {
        if ($this->psAccountsLoginEnabled && $tpl_name === $this->template) {
            return $this->createPsAccountsLoginTemplate();
        }

        return parent::createTemplate($tpl_name);
    }

    /**
     * @return Smarty_Internal_Template
     *
     * @throws SmartyException
     */
    public function createPsAccountsLoginTemplate()
    {
        /** @var \PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2ClientShopProvider $provider */
        $provider = $this->psAccountsModule->getService(\PrestaShop\OAuth2\Client\Provider\PrestaShop::class);

        $this->context->smarty->assign('oauthRedirectUri', $provider->getRedirectUri());
        $this->context->smarty->assign('legacyLoginUri', $this->context->link->getAdminLink('AdminLogin', true, [], [
            'mode' => self::PS_ACCOUNTS_LOGIN_MODE_LOCAL,
        ]));

        $isoCode = $this->context->currentLocale->getCode();

        $this->context->smarty->assign('uriHelpCenter', $this->getUriHelpCenter($isoCode));
        $this->context->smarty->assign('loginError', Tools::getValue('loginError'));
        $this->context->smarty->assign('meta_title', '');
        $this->context->smarty->assign('ssoResendVerificationEmail',
            $this->psAccountsModule->getParameter('ps_accounts.sso_resend_verification_email_url')
        );

        return $this->context->smarty->createTemplate(
            $this->getPsAccountsTemplateDir() . $this->template, $this->context->smarty
        );
    }

    /**
     * @return mixed
     */
    public function getPsAccountsLoginMode()
    {
        return Tools::getValue('mode');
    }

    /**
     * @return string
     */
    public function getPsAccountsTemplateDir()
    {
        return _PS_MODULE_DIR_ .
            DIRECTORY_SEPARATOR . 'ps_accounts' .
            DIRECTORY_SEPARATOR . 'views' .
            DIRECTORY_SEPARATOR . 'templates' .
            DIRECTORY_SEPARATOR . 'override' .
            DIRECTORY_SEPARATOR . 'controllers' .
            DIRECTORY_SEPARATOR . 'login' .
            DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $isoCode
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function getUriHelpCenter(string $isoCode)
    {
        return 0 !== strpos($isoCode, 'fr')
            ? $this->psAccountsModule->getParameter('ps_accounts.uri_help_center_en')
            : $this->psAccountsModule->getParameter('ps_accounts.uri_help_center_fr');
    }
}
