<?php

namespace PrestaShop\Module\PsAccounts\Controller\Admin;

//use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Api\Client\ExternalAssetsClient;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\AccountLoginException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmployeeNotFoundException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\OAuth2;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Entity\Employee\Employee as EmployeeEntity;
use Ps_accounts;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ExternalAssetsClient
     */
    private $externalAssetsClient;

    /**
     * @var RedirectResponse
     */
    private $redirectResponse;

    public function __construct()
    {
        /** @var Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');
        $this->module = $module;
        $this->link = $this->module->getService(Link::class);
        $this->analyticsService = $this->module->getService(AnalyticsService::class);
        $this->psAccountsService = $this->module->getService(PsAccountsService::class);
        $this->externalAssetsClient = $this->module->getService(ExternalAssetsClient::class);
    }

    /**
     * @param Request $request
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     *
     * @return RedirectResponse
     */
    public function initOAuth2FlowAction(
        Request $request,
        Security $security,
        EntityManagerInterface $entityManager
    ) {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->session = $request->getSession();

        try {
            return $this->oauth2Login();
        } catch (AccountLoginException $e) {
            return $this->onLoginFailed($e);
        } catch (\Exception $e) {
            return $this->onLoginFailed(new AccountLoginException($e->getMessage(), null, $e));
        }

        // TODO: cache cleanup / cache directory
        // TODO: factoriser les deux controlleurs
    }

    /**
     * @param Request $request
     *
     * @return Response|null
     *
     * @throws \PrestaShopException
     *
     * TODO: translations sf style
     */
    public function displayLoginAction(Request $request)
    {
        /** @var OAuth2\ShopProvider $provider */
        $provider = $this->module->getService(OAuth2\ShopProvider::class);
        // FIXME: get current locale
        $isoCode = 'en'; //$this->getContext()->getCurrentLocale()->getCode();

        // FIXME: extends login layout
        return $this->render('@Modules/ps_accounts/templates/admin/login.html.twig', [
            /* @phpstan-ignore-next-line */
            'shopUrl' => $this->getContext()->shop->getBaseUrl(true),
            //'oauthRedirectUri' => $this->generateUrl('ps_accounts_oauth2'),
            'oauthRedirectUri' => $provider->getRedirectUri(),
            'legacyLoginUri' => $this->generateUrl('admin_login', [
                'mode' => 'local',
            ]),
            'isoCode' => substr($isoCode, 0, 2),
            'locale' => substr($isoCode, 0, 2),
            'defaultIsoCode' => 'en',
            'testimonials' => $this->getTestimonials(),
            'loginError' => $request->getSession()->remove('loginError'),
            'meta_title' => '',
            'ssoResendVerificationEmail' => $this->module->getParameter(
                'ps_accounts.sso_resend_verification_email_url'
            ),
            // FIXME: get intended redirect uri
            'redirect' => '',
            // FIXME: integration with the appropriate login layout & blocks
            'linkCss' => '/modules/ps_accounts/views/css/login.css',
            'linkJs' => '/modules/ps_accounts/views/js/login.js',
        ]);
    }

    /**
     * @return OAuth2\ShopProvider
     */
    protected function getProvider()
    {
        return $this->module->getService(Oauth2\ShopProvider::class);
    }

    /**
     * @param PrestaShopUser $user
     *
     * @return bool
     *
     * @throws EmailNotVerifiedException
     * @throws EmployeeNotFoundException
     */
    protected function initUserSession(PrestaShopUser $user)
    {
        Logger::getInstance()->error(
            '[OAuth2] ' . (string) json_encode($user->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

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

        $authenticator = 'security.authenticator.form_login.main';
        $employeeRepository = $this->entityManager->getRepository(EmployeeEntity::class);
        $employeeEntity = $employeeRepository->findById($context->employee->id)[0];

        $response = $this->security->login($employeeEntity, $authenticator);

        // FIXME: what if no redirect response ?
        if ($response instanceof RedirectResponse) {
            $this->redirectResponse = $response;
        }

        $this->trackEditionLoginEvent($user);

        return true;
    }

    /**
     * @return RedirectResponse
     */
    protected function redirectAfterLogin()
    {
        // FIXME: requires some testing
        return $this->redirectResponse;
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
            $this->trackEditionLoginFailedEvent($e);
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
     * @return array
     */
    private function getTestimonials()
    {
        $res = $this->externalAssetsClient->getTestimonials();

        return $res['status'] ? $res['body'] : [];
    }
}
