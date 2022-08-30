<?php

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

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

    public function __construct()
    {
        parent::__construct();

        /** @var Ps_accounts $module */
        $this->psAccountsModule = Module::getInstanceByName('ps_accounts');

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->psAccountsModule->getService(ConfigurationRepository::class);

        if (self::PS_ACCOUNTS_LOGIN_MODE_LOCAL !== $this->getPsAccountsLoginMode()) {
            $this->psAccountsLoginEnabled = $configuration->getLoginEnabled();
        }
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

    public function createPsAccountsLoginTemplate()
    {
        /** @var \PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2ClientShopProvider $provider */
        $provider = $this->psAccountsModule->getService(\PrestaShop\OAuth2\Client\Provider\PrestaShop::class);

        $this->context->smarty->assign('oauthRedirectUri', $provider->getRedirectUri());
        $this->context->smarty->assign('legacyLoginUri', $this->context->link->getAdminLink('AdminLogin', true, [], [
            'mode' => self::PS_ACCOUNTS_LOGIN_MODE_LOCAL,
        ]));

        $this->context->smarty->assign('loginError', Tools::getValue('loginError'));

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
}
