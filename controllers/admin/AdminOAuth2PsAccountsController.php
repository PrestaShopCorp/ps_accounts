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

use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\AccountLoginException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmployeeNotFoundException;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\AdminController\IsAnonymousAllowed;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopLoginTrait;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\EmployeeAccountRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Controller for all ajax calls.
 */
class AdminOAuth2PsAccountsController extends \ModuleAdminController
{
    use PrestaShopLoginTrait;
    use IsAnonymousAllowed;

    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * @var AnalyticsService
     */
    private $analyticsService;

    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * @throws PrestaShopException
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->analyticsService = $this->module->getService(AnalyticsService::class);
        $this->psAccountsService = $this->module->getService(PsAccountsService::class);

        $this->ajax = true;
        $this->content_only = true;
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
     * @return void
     *
     * @throws PrestaShopException
     */
    //public function display()
    public function init()
    {
        try {
            $this->oauth2Login();
        } catch (AccountLoginException $e) {
            $this->onLoginFailed($e);
        } catch (Exception $e) {
            $this->onLoginFailed(new AccountLoginException($e->getMessage(), null, $e));
        }
        parent::init();
    }

    /**
     * @param PrestaShopUser $user
     *
     * @return bool
     *
     * @throws EmailNotVerifiedException
     * @throws EmployeeNotFoundException
     * @throws Exception
     */
    private function initUserSession(PrestaShopUser $user)
    {
        $this->oauth2ErrorLog((string) json_encode($user->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $context = $this->context;

        $emailVerified = $user->getEmailVerified();

        $context->employee = $this->getEmployeeByUidOrEmail($user->getId(), $user->getEmail());

        if (!$context->employee->id || empty($emailVerified)) {
            $context->employee->logout();

            if (empty($emailVerified)) {
                throw new EmailNotVerifiedException('Your account email is not verified', $user);
            }
            throw new EmployeeNotFoundException('The email address is not associated to a PrestaShop backoffice account.', $user);
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

        if (class_exists('EmployeeSession') && method_exists($cookie, 'registerSession')) {
            $cookie->registerSession(new EmployeeSession());
        }

        if (!Tools::getValue('stay_logged_in')) {
            /* @phpstan-ignore-next-line  */
            $cookie->last_activity = time();
        }

        $cookie->write();

        $this->trackLoginEvent($user);

        return true;
    }

    /**
     * @param AccountLoginException $e
     *
     * @return void
     *
     * @throws PrestaShopException
     * @throws Exception
     */
    private function onLoginFailed(AccountLoginException $e)
    {
        if ($this->module->isShopEdition() && (
                $e instanceof EmployeeNotFoundException ||
                $e instanceof EmailNotVerifiedException
            )) {
            $this->trackLoginFailedEvent($e);
        }

        $this->oauth2ErrorLog($e->getMessage());
        $this->setLoginError($e->getType());
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminLogin', true, [], [
                'logout' => 1,
            ])
        );
    }

    /**
     * @return ShopProvider
     *
     * @throws Exception
     */
    private function getProvider()
    {
        return $this->module->getService(ShopProvider::class);
    }

    /**
     * @return void
     */
    private function redirectAfterLogin()
    {
        $returnTo = $this->getSessionReturnTo() ?: 'AdminDashboard';
        if (preg_match('/^([A-Z][a-z0-9]+)+$/', $returnTo)) {
            $returnTo = $this->context->link->getAdminLink($returnTo);
        }
        Tools::redirectAdmin($returnTo);
    }

    /**
     * @return SessionInterface
     */
    private function getSession()
    {
        return $this->module->getSession();
    }

    /**
     * @param mixed $error
     *
     * @return void
     */
    private function setLoginError($error)
    {
        $this->getSession()->set('loginError', $error);
    }

    /**
     * @return PrestaShopSession
     */
    protected function getOauth2Session()
    {
        return $this->module->getService(PrestaShopSession::class);
    }

    /**
     * @param PrestaShopUser $user
     *
     * @return void
     *
     * @throws Exception
     */
    private function trackLoginEvent(PrestaShopUser $user)
    {
        if ($this->module->isShopEdition()) {
            $this->analyticsService->identify(
                $user->getId(),
                $user->getName(),
                $user->getEmail()
            );
            $this->analyticsService->group(
                $user->getId(),
                (string) $this->psAccountsService->getShopUuid()
            );
            $this->analyticsService->trackUserSignedIntoApp(
                $user->getId(),
                'smb-edition'
            );
        }
    }

    /**
     * @param EmployeeNotFoundException|EmailNotVerifiedException $e
     *
     * @return void
     *
     * @throws Exception
     */
    private function trackLoginFailedEvent($e)
    {
        $user = $e->getUser();
        $this->analyticsService->identify(
            $user->getId(),
            $user->getName(),
            $user->getEmail()
        );
        $this->analyticsService->group(
            $user->getId(),
            (string) $this->psAccountsService->getShopUuid()
        );
        $this->analyticsService->trackBackOfficeSSOSignInFailed(
            $user->getId(),
            $e->getType(),
            $e->getMessage()
        );
    }

    /**
     * @param string $uid
     * @param string $email
     *
     * @return Employee
     *
     * @throws Exception
     */
    private function getEmployeeByUidOrEmail($uid, $email)
    {
        $repository = new EmployeeAccountRepository();

        try {
            $employeeAccount = $repository->findByUid($uid);

            /* @phpstan-ignore-next-line */
            if ($employeeAccount) {
                $employee = new Employee($employeeAccount->getEmployeeId());
            } else {
                $employeeAccount = new EmployeeAccount();
                $employee = new Employee();
                $employee->getByEmail($email);
            }

            // Update account
            if ($employee->id) {
                $repository->upsert(
                    $employeeAccount
                        ->setEmployeeId($employee->id)
                        ->setUid($uid)
                        ->setEmail($email)
                );
            }
        } catch (\Exception $e) {
            $employee = new Employee();
            $employee->getByEmail($email);
        }

        return $employee;
    }
}
