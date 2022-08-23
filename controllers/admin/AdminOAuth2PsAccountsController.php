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

use PrestaShop\Module\PsAccounts\Provider\OAuth2\LoginData;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2LoginTrait;
use PrestaShopCorp\OAuth2\Client\Provider\PrestaShop;

/**
 * Controller for all ajax calls.
 */
class AdminOAuth2PsAccountsController extends ModuleAdminController
{
    use Oauth2LoginTrait;

    /**
     * @var Ps_accounts
     */
    public $module;

    public function __construct()
    {
        parent::__construct();

        $this->ajax = true;
        $this->content_only = true;
    }

    /**
     * Do not assert authenticated access
     *
     * @return bool
     */
    protected function isAnonymousAllowed() {
        return true;
    }

    public function display()
    {
        $this->oauth2Login();
    }

    // FIXME: is there a way to not duplicate that code (from ps core) ?
    function initUserSession(LoginData $loginData): bool
    {
        $context = $this->context;

        $emailVerified = $loginData->emailVerified;
        $context->employee = new Employee();
        $isEmployedLoaded = $context->employee->getByEmail($loginData->email);

        if (!$isEmployedLoaded || empty($emailVerified))
        {
            $context->employee->logout();
            // TODO: redirect SSO logout
            exit(empty($emailVerified) ? "You account is not verified" : "The employee does not exist");
        }

        $context->employee->remote_addr = (int) ip2long(Tools::getRemoteAddr());

        $cookie = $context->cookie;
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

        return true;
    }

    function getProvider(): PrestaShop
    {
        return $this->module->getService(PrestaShop::class);
    }

    function redirectAfterLogin(): void
    {
        $returnTo = $this->getSessionReturnTo();
        $this->redirectJs(
            !empty($returnTo) ? $returnTo : $this->context->link->getAdminLink('AdminDashboard')
        );
    }

    function redirectRegistrationForm(LoginData $loginData): void
    {
        // TODO: Implement redirectRegistrationForm() method.
    }

    private function startSession()
    {
        // TODO: Implement startSession() method.
        session_start();
    }

    private function destroySession()
    {
        // TODO: Implement destroySession() method.
    }

    /**
     * @param string $url
     *
     * @return void
     */
    public function redirectJs(string $url)
    {
        if ($url) {
            echo <<<JS
<script>
    const redirect = '$url';
    if (opener)
        opener.location = redirect;
    else
        window.location = redirect;
</script>
JS;
        } else {
            echo <<<JS
<script>
    if (opener)
        window.close();
</script>
JS;
        }
        exit;
    }
}
