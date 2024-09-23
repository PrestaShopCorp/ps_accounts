<?php

namespace PrestaShop\Module\PsAccounts\Controller\Admin;

//use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Employee;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Api\Client\ExternalAssetsClient;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\AccountLoginException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmployeeNotFoundException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Entity\Employee\Employee as EmployeeEntity;
use Ps_accounts;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
     * @var SessionInterface
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

    /**
     * @var ExternalAssetsClient
     */
    private $externalAssetsClient;

    public function __construct()
    {
        $this->module = \Module::getInstanceByName('ps_accounts');
        $this->link = $this->module->getService(Link::class);
        $this->session = $this->module->getSession();
        $this->analyticsService = $this->module->getService(AnalyticsService::class);
        $this->psAccountsService = $this->module->getService(PsAccountsService::class);
        $this->externalAssetsClient = $this->module->getService(ExternalAssetsClient::class);
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

        // TODO: access the session from DI
        // TODO: fix the EmployeeAccount bug
        // TODO: upgrade script (cleanup files)
        // TODO: update oauth2 client
        // TODO: try to preserve original uris with legacy_link & legacy_controllers & supprimer l'ancien controller
        // TODO: refactor logout (listen sf events)
        // TODO: fix compromised message on login
        // TODO: implement logout
        // TODO: compat with v8
        // -- API
        // TODO: update oauth2 clients redirect_uri
        // TODO: factoriser les deux controlleurs
        // TODO: cleanup files from previous updates (big cleanup)
        // TODO: update oauth clients & implement new local state based upgrade process
    }

    public function displayLogin()
    {
        /** @var OAuth2\ShopProvider $provider */
        $provider = $this->module->getService(OAuth2\ShopProvider::class);
        $session = $this->module->getSession();
        // FIXME
        $isoCode = 'en'; //$this->getContext()->getCurrentLocale()->getCode();

        return $this->render('@Modules/ps_accounts/templates/admin/login.html.twig', [
            /* @phpstan-ignore-next-line */
            'shopUrl' => $this->getContext()->shop->getBaseUrl(true),
            //'oauthRedirectUri' => $this->generateUrl('ps_accounts_oauth2'),
            'oauthRedirectUri' => $provider->getRedirectUri(),
            'legacyLoginUri' => $this->generateUrl('admin_login', [
                'mode' => 'local'
            ]),
            'isoCode' => substr($isoCode, 0, 2),
            'locale' => substr($isoCode, 0, 2),
            'defaultIsoCode' => 'en',
            'testimonials' => $this->getTestimonials(),
            'loginError' => $session->remove('loginError'),
            'meta_title' => '',
            'ssoResendVerificationEmail' => $this->module->getParameter(
                'ps_accounts.sso_resend_verification_email_url'
            ),
            // FIXME
            'redirect' => '',
            // FIXME: integration with the appropriate login layout & blocks
            'linkCss' => '/modules/ps_accounts/views/css/login.css',
            'linkJs' => '/modules/ps_accounts/views/js/login.js',
        ]);
    }

    /**
     * @return array
     */
    private function getTestimonials()
    {
        $res = $this->externalAssetsClient->getTestimonials(
            $this->module->getParameter('ps_accounts.testimonials_url')
        );

        return $res['status'] ? $res['body'] : [];
    }

//    public function displayLocalLogin()
//    {
//        return $this->forward(PrestaShopBundle\Controller\Admin\LoginController::class . '@loginAction');
//    }

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

        // $authenticator = 'security.authenticator.remember_me.main'
        $authenticator = 'security.authenticator.form_login.main';
        $employeeRepository = $this->entityManager->getRepository(EmployeeEntity::class);
        $employeeEntity = $employeeRepository->findById($context->employee->id);
        $this->security->login($employeeEntity[0], $authenticator);

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
     * @return SessionInterface
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
