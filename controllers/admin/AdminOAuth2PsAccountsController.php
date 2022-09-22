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
use PrestaShop\Module\PsAccounts\Exception\EmployeeAccountEmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\Exception\EmployeeAccountNotFoundException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2ClientShopProvider;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2LoginTrait;
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
            $this->oauth2ErrorLog($e->getMessage());
            $this->redirectWithError('error_from_hydra');
        } catch (EmployeeAccountEmailNotVerifiedException $e) {
            $this->oauth2ErrorLog($e->getMessage());
            $this->redirectWithError('email_not_verified');
        } catch (EmployeeAccountNotFoundException $e) {
            $this->oauth2ErrorLog($e->getMessage());
            $this->redirectWithError('employee_not_found');
        } catch (Exception $e) {
            $this->oauth2ErrorLog($e->getMessage());
            $this->redirectWithError('error_other');
        }
    }

    /**
     * @param PrestaShopUser $user
     *
     * @return bool
     *
     * @throws ContainerNotFoundException
     * @throws EmployeeAccountEmailNotVerifiedException
     * @throws EmployeeAccountNotFoundException
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
                throw new EmployeeAccountEmailNotVerifiedException('Your account email is not verified');
            }
            throw new EmployeeAccountNotFoundException('The email address is not associated to a PrestaShop backoffice account.');
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
     * @param string $error
     *
     * @return void
     */
    private function redirectWithError(string $error): void
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminLogin', true, [], [
                'logout' => 1,
                'loginError' => $error,
            ])
        );
    }

    private function getProvider(): Oauth2ClientShopProvider
    {
        return $this->module->getService(PrestaShop::class);
    }

    private function redirectAfterLogin(): void
    {
        $returnTo = $this->getSessionReturnTo();
        Tools::redirectAdmin(
            !empty($returnTo) ? $returnTo : $this->context->link->getAdminLink('AdminDashboard')
        );
    }

    /**
     * @throws ContainerNotFoundException
     */
    private function getSession(): SessionInterface
    {
        /* @phpstan-ignore-next-line */
        return $this->module->getContainer()->get('session');
    }
}
