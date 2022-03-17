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

use PrestaShop\Module\PsAccounts\Handler\Error\Sentry;
use PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

/**
 * Controller for all ajax calls.
 */
class AdminPsAccountsSsoConnectController extends ModuleAdminController
{
    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * AdminAjaxPsAccountsController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function isAnonymousAllowed() {
        return true;
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    public function ajaxProcessSsoConnect()
    {
        $token = trim(Tools::getValue('token'));
        $refreshToken = trim(Tools::getValue('refreshToken'));
        $provider = trim(Tools::getValue('provider'));

        if ($provider !== "ps_accounts" || empty($refreshToken) || empty($token))
            $this->ajaxDie(json_encode(['fail' => 'unauthorized']));

        /** @var \PrestaShop\Module\PsAccounts\Api\Client\SsoClient $ssoClient */
        $ssoClient = $this->module->get(\PrestaShop\Module\PsAccounts\Api\Client\SsoClient::class);

        $ret = $ssoClient->verifyToken($token);

        if ($ret['status'] === false)
            $this->ajaxDie(json_encode(['fail' => 'unauthorized']));

        /** @var \PrestaShop\Module\PsAccounts\Repository\UserTokenRepository $userTokenRepository */
        $userTokenRepository = $this->module->get(\PrestaShop\Module\PsAccounts\Repository\UserTokenRepository::class);

        $parsedToken = $userTokenRepository->parseToken($token);

        $jwtEmail = $parsedToken->claims()->get('email');
        $emailVerified = $parsedToken->claims()->get('email_verified');
        $context = Context::getContext();
        $context->employee = new Employee();
        $isEmployedLoaded = $context->employee->getByEmail($jwtEmail);

        if (!$isEmployedLoaded || empty($emailVerified))
        {
            $context->employee->logout();
            $this->ajaxDie(
                json_encode(
                    [
                        'hasErrors' => true,
                        'errors' => [
                            empty($emailVerified) ? "You account is not verified" : "The employee does not exist"
                        ]
                    ]
                )
            );
        }

        $context->employee->remote_addr = (int) ip2long(Tools::getRemoteAddr());

        $cookie = Context::getContext()->cookie;
        $cookie->id_employee = $context->employee->id;
        $cookie->email = $context->employee->email;
        $cookie->profile = $context->employee->id_profile;
        $cookie->passwd = $context->employee->passwd;
        $cookie->remote_addr = $context->employee->remote_addr;

        if (intval(_PS_VERSION_[0]) >= 8)
            $cookie->registerSession(new EmployeeSession());

        if (!Tools::getValue('stay_logged_in'))
            $cookie->last_activity = time();

        $cookie->write();

//        $url = $context->link->getAdminLink($_POST['redirect']);

        $this->ajaxDie(json_encode(['hasErrors' => false, 'redirect' => $_POST['redirect']]));
    }
}
