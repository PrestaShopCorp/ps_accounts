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

namespace PrestaShop\Module\PsAccounts\Controller\Admin;

//use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\AccountLoginException;
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\EmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\EmployeeNotFoundException;
use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2LoginTrait;
use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2Session;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Api\Client\ExternalAssetsClient;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\UserInfo;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
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
    use OAuth2LoginTrait;

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
    }

    /**
     * @param Request $request
     *
     * @return Response|null
     *
     * @throws \PrestaShopException
     */
    public function displayLoginAction(Request $request)
    {
        $isoCode = $this->getContext()->getCurrentLocale()->getCode();

        // FIXME: extends login layout
        return $this->render('@Modules/ps_accounts/templates/admin/login.html.twig', [
            /* @phpstan-ignore-next-line */
            'shopUrl' => $this->getContext()->shop->getBaseUrl(true),
            //'oauthRedirectUri' => $this->generateUrl('ps_accounts_oauth2'),
            'oauthRedirectUri' => $this->getOAuth2Service()->getOAuth2Client()->getRedirectUri(),
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
            'linkCss' => $this->module->getPathUri() . '/views/css/login.css',
            'linkJs' => $this->module->getPathUri() . '/views/js/login.js',
        ]);
    }

    /**
     * @return OAuth2Service
     */
    protected function getOAuth2Service()
    {
        return $this->module->getService(OAuth2Service::class);
    }

    /**
     * @param UserInfo $user
     *
     * @return bool
     *
     * @throws EmailNotVerifiedException
     * @throws EmployeeNotFoundException
     */
    protected function initUserSession(UserInfo $user)
    {
        Logger::getInstance()->info(
            '[OAuth2] ' . (string) json_encode($user->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        //$context = $this->context;
        /** @var \Context $context */
        $context = $this->module->getService('ps_accounts.context');

        $emailVerified = $user->email_verified;

        $context->employee = $this->getEmployeeByUidOrEmail($user->sub, $user->email);

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
     * @return RedirectResponse
     */
    protected function logout()
    {
        return $this->redirect(
            $this->link->getAdminLink('AdminLogin', true, [], [
                'logout' => 1,
            ])
        );
    }

    /**
     * @return RedirectResponse
     */
    protected function onLoginFailedRedirect()
    {
        return $this->redirect(
            $this->getSessionReturnTo() ?:
                $this->link->getAdminLink('AdminDashboard')
        );
    }

    /**
     * @return SessionInterface
     */
    protected function getSession()
    {
        return $this->session;
    }

    /**
     * @return OAuth2Session
     */
    protected function getOauth2Session()
    {
        return $this->module->getService(OAuth2Session::class);
    }

    /**
     * @return AnalyticsService
     */
    protected function getAnalyticsService()
    {
        return $this->analyticsService;
    }

    /**
     * @return PsAccountsService
     */
    protected function getPsAccountsService()
    {
        return $this->psAccountsService;
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
