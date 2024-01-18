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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AdminLoginPsAccountsController extends AdminLoginControllerCore
{
    const PARAM_MODE_LOCAL = 'local';

    /** @var string */
    public $template = 'login.tpl';

    /** @var bool */
    private $loginWithPsAccounts = false;

    /** @var Ps_accounts */
    private $psAccounts;

    /** @var AnalyticsService */
    private $analyticsService;

    /** @var Logger */
    private $logger;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        /** @var Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        $this->psAccounts = $module;

        $this->logger = $this->psAccounts->getLogger();

        /** @var PsAccountsService $moduleService */
        $moduleService = $this->psAccounts->getService(PsAccountsService::class);

        $this->loginWithPsAccounts =  (Tools::getValue('mode') !== self::PARAM_MODE_LOCAL) &&
            $moduleService->getLoginActivated();

        $this->analyticsService = $this->psAccounts->getService(AnalyticsService::class);
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

    /* @phpstan-ignore-next-line */
    public function setMedia($isNewTheme = false)
    {
        if ($this->loginWithPsAccounts) {
            $this->addCss(_PS_MODULE_DIR_ . 'ps_accounts/views/css/login.css');
            $this->addJS(_PS_MODULE_DIR_ . '/ps_accounts/views/js/login.js');

            //Hook::exec('actionAdminLoginControllerSetMedia');

            return;
        }

        parent::setMedia();
    }

    public function init()
    {
        parent::init();
    }

    /**
     * Create a template from the override file, else from the base file.
     *
     * @param string $tpl_name filename
     *
     * @return Smarty_Internal_Template
     *
     * @throws SmartyException|Exception
     */
    public function createTemplate($tpl_name)
    {
        if ($this->psAccounts->isShopEdition()) {
            /** @var PsAccountsService $psAccountsService */
            $psAccountsService = $this->psAccounts->getService(PsAccountsService::class);
            $account = $psAccountsService->getEmployeeAccount();
            $userId = $account ? $account->getUid() : null;

            $this->loginWithPsAccounts ?
                $this->analyticsService->pageAccountsBoLogin($userId) :
                $this->analyticsService->pageLocalBoLogin($userId);
        }

        try {
            if ($this->loginWithPsAccounts && $tpl_name === $this->template) {
                return $this->createPsAccountsLoginTemplate();
            }
        } catch (\Exception $e) {
            $this->logger->error('Error while creating the ps accounts login template', ['error' => $e->getMessage()]);
        }

        return parent::createTemplate($tpl_name);
    }

    /**
     * @return Smarty_Internal_Template
     *
     * @throws SmartyException
     * @throws Exception
     */
    protected function createPsAccountsLoginTemplate()
    {
        /** @var PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopClientProvider $provider */
        $provider = $this->psAccounts->getService(PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopClientProvider::class);

        $testimonials = $this->getTestimonials();

        /** @var SessionInterface $session */
        $session = $this->psAccounts->getSession();

        $this->context->smarty->assign('shopUrl', $this->context->shop->getBaseUrl(true));

        $this->context->smarty->assign('oauthRedirectUri', $provider->getRedirectUri());
        $this->context->smarty->assign('legacyLoginUri', $this->context->link->getAdminLink('AdminLogin', true, [], [
            'mode' => self::PARAM_MODE_LOCAL,
        ]));

        $isoCode = $this->context->currentLocale->getCode();
        $this->context->smarty->assign('isoCode', substr($isoCode, 0, 2));
        $this->context->smarty->assign('defaultIsoCode', 'en');
        $this->context->smarty->assign('testimonials', $testimonials);

        $this->context->smarty->assign('loginError', $session->remove('loginError'));
        $this->context->smarty->assign('meta_title', '');
        $this->context->smarty->assign('ssoResendVerificationEmail',
            $this->psAccounts->getParameter('ps_accounts.sso_resend_verification_email_url')
        );

        return $this->context->smarty->createTemplate(
            $this->psAccounts->getLocalPath() . '/views/templates/admin/' . $this->template,
            $this->context->smarty
        );
    }

    /**
     * @return array
     */
    private function getTestimonials()
    {
        try {
            $client = new Client();
            $resp = $client->get($this->psAccounts->getParameter('ps_accounts.testimonials_url'));

            return json_decode($resp->getBody()->getContents());
        } catch (GuzzleException $e) {
            $this->logger->error('Error while getting the testimonials', ['error' => $e->getMessage()]);

            return [];
        } catch (Exception $e) {
            $this->logger->error('Error while getting the testimonials', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
