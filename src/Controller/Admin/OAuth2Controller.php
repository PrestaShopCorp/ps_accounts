<?php

namespace PrestaShop\Module\PsAccounts\Controller\Admin;

//use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Employee;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\AccountLoginException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmployeeNotFoundException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\Oauth2Exception;
use PrestaShop\Module\PsAccounts\Provider\OAuth2;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Session\Session;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Entity\Employee\Employee as EmployeeEntity;
use Ps_accounts;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OAuth2Controller extends FrameworkBundleAdminController
{
    use OAuth2\PrestaShopLoginTrait;

    /**
     * @var Ps_accounts
     */
    private $module;

    /**
     * @var AnalyticsService
     */
    private $analyticsService;

    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * @var Link
     */
    private $link;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct()
    {
        $this->module = \Module::getInstanceByName('ps_accounts');
        $this->link = $this->module->getService(Link::class);
        $this->session = $this->module->getSession();
        $this->analyticsService = $this->module->getService(AnalyticsService::class);
        $this->psAccountsService = $this->module->getService(PsAccountsService::class);
    }

    /**
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     *
     * @return RedirectResponse
     */
    public function initOAuth2Flow(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;

        try {
            return $this->oauth2Login();
        } catch (AccountLoginException $e) {
            return $this->onLoginFailed($e);
        } catch (\Exception $e) {
            return $this->onLoginFailed(new AccountLoginException($e->getMessage() . ' ' . $e->getTraceAsString(), null, $e));
        }

        // TODO: create a flashlight image with the anonymous fix PR
        // TODO: access the session from DI
        // TODO: preserve legacy cookie login
        // TODO: fix the EmployeeAccount bug
        // TODO: upgrade script (cleanup files)
        // TODO: update oauth2 client
        // TODO: FIXME: migrate getTestimonials
        // TODO: try to preserve original uris with legacy_link & legacy_controllers & supprimer l'ancien controller
        // TODO: refactor logout (listen sf events)
    }

    /**
     * @return OAuth2\ShopProvider
     */
    protected function getProvider()
    {
        return $this->module->getService(Oauth2\ShopProvider::class);
    }

    protected function initUserSession(PrestaShopUser $user)
    {
        $this->oauth2ErrorLog((string) json_encode($user->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        //$context = $this->context;
        /** @var \Context $context */
        $context = $this->module->getService('ps_accounts.context');

        $emailVerified = $user->getEmailVerified();

        $context->employee = $this->getEmployeeByUidOrEmail($user->getId(), $user->getEmail());

        if (!$context->employee->id || empty($emailVerified)) {
            if ($context->employee->isLoggedBack()) {
                //$context->employee->logout();
                $this->security->logout();
            }

            if (empty($emailVerified)) {
                throw new EmailNotVerifiedException('Your account email is not verified', $user);
            }
            throw new EmployeeNotFoundException('The email address is not associated to a PrestaShop backoffice account.', $user);
        }

//        $context->employee->remote_addr = (int) ip2long(Tools::getRemoteAddr());

        // $authenticator = 'security.authenticator.remember_me.main'
        $authenticator = 'security.authenticator.form_login.main';
        $employeeRepository = $this->entityManager->getRepository(EmployeeEntity::class);
        $employeeEntity = $employeeRepository->findById($context->employee->id);
        $this->security->login($employeeEntity[0], $authenticator);

//        $cookie = $context->cookie;
//        /* @phpstan-ignore-next-line  */
//        $cookie->id_employee = $context->employee->id;
//        /* @phpstan-ignore-next-line  */
//        $cookie->email = $context->employee->email;
//        /* @phpstan-ignore-next-line  */
//        $cookie->profile = $context->employee->id_profile;
//        /* @phpstan-ignore-next-line  */
//        $cookie->passwd = $context->employee->passwd;
//        /* @phpstan-ignore-next-line  */
//        $cookie->remote_addr = $context->employee->remote_addr;
//
//        if (class_exists('EmployeeSession') && method_exists($cookie, 'registerSession')) {
//            $cookie->registerSession(new EmployeeSession());
//        }
//
//        if (!Tools::getValue('stay_logged_in')) {
//            /* @phpstan-ignore-next-line  */
//            $cookie->last_activity = time();
//        }
//
//        $cookie->write();

        $this->trackLoginEvent($user);

        return true;
    }

    /**
     * @return RedirectResponse
     */
    protected function redirectAfterLogin()
    {
        $returnTo = $this->getSessionReturnTo() ?: 'AdminDashboard';
        if (preg_match('/^([A-Z][a-z0-9]+)+$/', $returnTo)) {
            $returnTo = $this->link->getAdminLink($returnTo, true);
        }
        //\Tools::redirectAdmin($returnTo);
        return $this->redirect($returnTo);
    }

    /**
     * @return Session
     */
    protected function getSession()
    {
        return $this->session;
    }

    /**
     * @return Oauth2\PrestaShopSession
     */
    protected function getOauth2Session()
    {
        return $this->module->getService(Oauth2\PrestaShopSession::class);
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
     * @param AccountLoginException $e
     *
     * @return RedirectResponse
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
        return $this->redirect(
            $this->link->getAdminLink('AdminLogin', true, [], [
                'logout' => 1,
            ])
        );
    }

    /**
     * @param PrestaShopUser $user
     *
     * @return void
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
     */
    private function getEmployeeByUidOrEmail($uid, $email)
    {
//        $repository = new EmployeeAccountRepository();
//
//        try {
//            $employeeAccount = $repository->findByUid($uid);
//
//            /* @phpstan-ignore-next-line */
//            if ($employeeAccount) {
//                $employee = new Employee($employeeAccount->getEmployeeId());
//            } else {
//                $employeeAccount = new EmployeeAccount();
//                $employee = new Employee();
//                $employee->getByEmail($email);
//            }
//
//            // Update account
//            if ($employee->id) {
//                $repository->upsert(
//                    $employeeAccount
//                        ->setEmployeeId($employee->id)
//                        ->setUid($uid)
//                        ->setEmail($email)
//                );
//            }
//        } catch (\Exception $e) {
//            $employee = new Employee();
//            $employee->getByEmail($email);
//        }

        $employee = new Employee();
        if (Employee::employeeExists($email)) {
            $employee->getByEmail($email);
        }

        return $employee;
    }
}
