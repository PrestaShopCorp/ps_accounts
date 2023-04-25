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

use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\AccountLoginException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmployeeNotFoundException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\Oauth2Exception;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\OtherErrorException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopClientProvider;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopLoginTrait;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException;
use PrestaShop\PrestaShop\Core\Exception\CoreException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Controller for all ajax calls.
 */
class AdminOAuth2PsAccountsController extends ModuleAdminController
{
    use PrestaShopLoginTrait;

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

    protected function isAnonymousAllowed(): bool
    {
        return true;
    }

    public function display(): void
    {
        try {
            $this->oauth2Login();
        } catch (IdentityProviderException $e) {
            $this->onLoginFailed(new Oauth2Exception(null, $e->getMessage()));
        } catch (EmailNotVerifiedException | EmployeeNotFoundException $e) {
            $this->onLoginFailed($e);
        } catch (Exception $e) {
            $this->onLoginFailed(new OtherErrorException(null, $e->getMessage()));
        }
    }

    /**
     * @param PrestaShopUser $user
     *
     * @return bool
     *
     * @throws ContainerNotFoundException
     * @throws EmailNotVerifiedException
     * @throws EmployeeNotFoundException
     * @throws CoreException
     */
    private function initUserSession(PrestaShopUser $user): bool
    {
        $context = $this->context;

        $emailVerified = $user->getEmailVerified();

        $context->employee = $this->getEmployeeByUidOrEmail($user->getId(), $user->getEmail());

        if (!$context->employee->id || empty($emailVerified)) {
            $context->employee->logout();

            if (empty($emailVerified)) {
                throw new EmailNotVerifiedException($user);
            }
            throw new EmployeeNotFoundException($user);
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

        return true;
    }

    /**
     * @throws ContainerNotFoundException
     */
    private function getEmployeeByUidOrEmail(string $uid, string $email): Employee
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->module->getContainer()->get('doctrine.orm.entity_manager');

        $employeeAccountRepository = $entityManager->getRepository(EmployeeAccount::class);

        /**
         * @var EmployeeAccount $employeeAccount
         * @phpstan-ignore-next-line
         */
        $employeeAccount = $employeeAccountRepository->findOneBy(['uid' => $uid]);
        // $employeeAccount = $employeeAccountRepository->findOneByUid($uid);

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
            $employeeAccount->setEmployeeId($employee->id)
                ->setUid($uid)
                ->setEmail($email);

            $entityManager->persist($employeeAccount);
            $entityManager->flush();
        }

        return $employee;
    }

    /**
     * @throws ContainerNotFoundException
     */
    private function onLoginFailed(AccountLoginException $e): void
    {
        if ($this->module->isShopEdition() && (
                $e instanceof EmployeeNotFoundException ||
                $e instanceof EmailNotVerifiedException
            )) {
            $user = $e->getPrestaShopUser();
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

        $this->oauth2ErrorLog($e->getMessage());
        $this->setLoginError($e->getType());
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminLogin', true, [], [
                'logout' => 1,
            ])
        );
    }

    /**
     * @throws Exception
     */
    private function getProvider(): PrestaShopClientProvider
    {
        return $this->module->getService(PrestashopClientProvider::class);
    }

    private function redirectAfterLogin(): void
    {
        $returnTo = $this->getSessionReturnTo() ?: 'AdminDashboard';
        if (preg_match('/^([A-Z][a-z0-9]+)+$/', $returnTo)) {
            $returnTo = $this->context->link->getAdminLink($returnTo);
        }
        Tools::redirectAdmin($returnTo);
    }

    /**
     * @throws ContainerNotFoundException
     */
    private function getSession(): SessionInterface
    {
        /* @phpstan-ignore-next-line  */
        return $this->module->getContainer()->get('session');
    }

    /**
     * @throws ContainerNotFoundException
     */
    private function setLoginError(string $error): void
    {
        $this->getSession()->set('loginError', $error);
    }

    /**
     * @throws Exception
     */
    protected function getOauth2Session(): PrestaShopSession
    {
        return $this->module->getService(PrestaShopSession::class);
    }
}
