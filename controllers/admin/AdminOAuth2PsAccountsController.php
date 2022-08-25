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

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2ClientShopProvider;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2LoginTrait;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;

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

    protected function isAnonymousAllowed(): bool
    {
        return true;
    }

    public function display(): void
    {
        try {
            $this->oauth2Login();
        } catch (IdentityProviderException $e) {
            // Failed to get the access token or user details.
            $this->oauth2ErrorLog($e->getMessage());

            $this->closePopupWithError($e->getMessage());
        } catch (Exception $e) {
            $this->oauth2ErrorLog($e->getMessage());

            $this->closePopupWithError($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function initUserSession(PrestaShopUser $user): bool
    {
        $context = $this->context;

        $emailVerified = $user->getEmailVerified();
        $context->employee = new Employee();
        $isEmployedLoaded = $context->employee->getByEmail($user->getEmail());

        if (!$isEmployedLoaded || empty($emailVerified)) {
            $context->employee->logout();
            throw new Exception(empty($emailVerified) ? 'You account is not verified' : 'The employee does not exist');
        }

        $context->employee->remote_addr = (int) ip2long(Tools::getRemoteAddr());

        $cookie = $context->cookie;
        /* @phpstan-ignore-next-line  */
        $cookie->id_employee = $context->employee->id;
        /* @phpstan-ignore-next-line  */
        $cookie->email = $context->employee->email;
        /* @phpstan-ignore-next-line  */
        $cookie->profile = $context->employee->id_profile;
        /* @phpstan-ignore-next-line  */
        $cookie->passwd = $context->employee->passwd;
        /* @phpstan-ignore-next-line  */
        $cookie->remote_addr = $context->employee->remote_addr;

        if (intval(_PS_VERSION_[0]) >= 8) {
            $cookie->registerSession(new EmployeeSession());
        }

        if (!Tools::getValue('stay_logged_in')) {
            /* @phpstan-ignore-next-line  */
            $cookie->last_activity = time();
        }

        $cookie->write();

        return true;
    }

    private function getProvider(): Oauth2ClientShopProvider
    {
        return $this->module->getService(PrestaShop::class);
    }

    /**
     * @param string $error
     *
     * @return void
     */
    private function closePopupWithError($error): void
    {
        $this->redirectJs(
            $this->context->link->getAdminLink('AdminLogin', true, [], [
                'logout' => 1,
                'loginError' => $error,
            ])
        );
    }

    private function redirectAfterLogin(): void
    {
        $returnTo = $this->getSessionReturnTo();
        $this->redirectJs(
            !empty($returnTo) ? $returnTo : $this->context->link->getAdminLink('AdminDashboard')
        );
    }

    private function redirectRegistrationForm(PrestaShopUser $user): void
    {
        // TODO: Implement redirectRegistrationForm() method.
    }

    private function startSession(): void
    {
        // TODO: Implement startSession() method.
        session_start();
    }

    private function destroySession(): void
    {
        // TODO: Implement destroySession() method.
    }

    private function redirectJs(string $url): void
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
