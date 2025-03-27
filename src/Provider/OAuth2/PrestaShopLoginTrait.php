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

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use Employee;
use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\AccountLoginException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\EmployeeNotFoundException;
use PrestaShop\Module\PsAccounts\Exception\AccountLogin\Oauth2Exception;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Repository\EmployeeAccountRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessToken;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tools;

trait PrestaShopLoginTrait
{
    /**
     * @return ShopProvider
     */
    abstract protected function getProvider();

    /**
     * @param PrestaShopUser $user
     *
     * @return bool
     */
    abstract protected function initUserSession(PrestaShopUser $user);

    /**
     * @return mixed
     */
    abstract protected function redirectAfterLogin();

    /**
     * @return mixed
     */
    abstract protected function logout();

    /**
     * @return SessionInterface
     */
    abstract protected function getSession();

    /**
     * @return PrestaShopSession
     */
    abstract protected function getOauth2Session();

    /**
     * @return AnalyticsService
     */
    abstract protected function getAnalyticsService();

    /**
     * @return PsAccountsService
     */
    abstract protected function getPsAccountsService();

    /**
     * @return mixed
     *
     * @throws EmailNotVerifiedException
     * @throws EmployeeNotFoundException
     * @throws Oauth2Exception
     * @throws \Exception
     */
    public function oauth2Login()
    {
        $provider = $this->getProvider();

        //$this->getSession()->start();
        $session = $this->getSession();
        $oauth2Session = $this->getOauth2Session();

        if (!empty($_GET['error'])) {
            // Got an error, probably user denied access
            throw new \Exception('Got error: ' . $_GET['error']);
        // If we don't have an authorization code then get one
        } elseif (!isset($_GET['code'])) {
            // cleanup existing accessToken
            $oauth2Session->clear();

            $this->setSessionReturnTo(Tools::getValue($this->getReturnToParam()));

            $this->oauth2Redirect(Tools::getValue('locale'));

        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || ($session->has('oauth2state') && $_GET['state'] !== $session->get('oauth2state'))) {
            $session->remove('oauth2state');

            throw new \Exception('Invalid state');
        } else {
            // Restore the PKCE code before the `getAccessToken()` call.
            $provider->setPkceCode($this->getSession()->get('oauth2pkceCode'));

            try {
                // Try to get an access token using the authorization code grant.
                /** @var AccessToken $accessToken */
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code'],
                ]);
            } catch (IdentityProviderException $e) {
                throw new Oauth2Exception($e->getMessage(), null, $e);
            }

            $oauth2Session->setTokenProvider($accessToken);

            if ($this->initUserSession($oauth2Session->getPrestashopUser())) {
                return $this->redirectAfterLogin();
            }
        }
    }

    /**
     * @param string $locale
     *
     * @return void
     *
     * @throws \Exception
     */
    private function oauth2Redirect($locale)
    {
        $provider = $this->getProvider();

        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $provider->getAuthorizationUrl([
            'ui_locales' => $locale,
            'prompt' => 'login',
        ]);

        // Store the PKCE code after the `getAuthorizationUrl()` call.
        //$_SESSION['oauth2pkceCode'] = $provider->getPkceCode();
        $this->getSession()->set('oauth2pkceCode', $provider->getPkceCode());

        // Get the state generated for you and store it to the session.
        $this->getSession()->set('oauth2state', $provider->getState());

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit;
    }

    /**
     * @param string $msg
     *
     * @return void
     *
     * @throws \Exception
     */
    private function oauth2ErrorLog($msg)
    {
        Logger::getInstance()->error('[OAuth2] ' . $msg);
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    private function getSessionReturnTo()
    {
        return $this->getSession()->get($this->getReturnToParam(), '');
    }

    /**
     * @param string $returnTo
     *
     * @return void
     *
     * @throws \Exception
     */
    private function setSessionReturnTo($returnTo)
    {
        $this->getSession()->set($this->getReturnToParam(), $returnTo);
    }

    /**
     * @return string
     */
    private function getReturnToParam()
    {
        return 'return_to';
    }

    /**
     * @param string $uid
     * @param string $email
     *
     * @return Employee
     */
    protected function getEmployeeByUidOrEmail($uid, $email)
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
                if (Employee::employeeExists($email)) {
                    $employee->getByEmail($email);
                }
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

    /**
     * @param AccountLoginException $e
     *
     * @return mixed
     */
    protected function onLoginFailed(AccountLoginException $e)
    {
        if ($this->module->isShopEdition() && (
                $e instanceof EmployeeNotFoundException ||
                $e instanceof EmailNotVerifiedException
            )) {
            $this->trackEditionLoginFailedEvent($e);
        }

        $this->oauth2ErrorLog($e->getMessage());
        $this->setLoginError($e->getType());

        return $this->logout();
    }

    /**
     * @param mixed $error
     *
     * @return void
     */
    protected function setLoginError($error)
    {
        $this->getSession()->set('loginError', $error);
    }

    /**
     * @param PrestaShopUser $user
     *
     * @return void
     */
    protected function trackEditionLoginEvent(PrestaShopUser $user)
    {
        if ($this->module->isShopEdition()) {
            $this->getAnalyticsService()->identify(
                $user->getId(),
                $user->getName(),
                $user->getEmail()
            );
            $this->getAnalyticsService()->group(
                $user->getId(),
                (string) $this->getPsAccountsService()->getShopUuid()
            );
            $this->getAnalyticsService()->trackUserSignedIntoApp(
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
    protected function trackEditionLoginFailedEvent($e)
    {
        $user = $e->getUser();
        $this->getAnalyticsService()->identify(
            $user->getId(),
            $user->getName(),
            $user->getEmail()
        );
        $this->getAnalyticsService()->group(
            $user->getId(),
            (string) $this->getPsAccountsService()->getShopUuid()
        );
        $this->getAnalyticsService()->trackBackOfficeSSOSignInFailed(
            $user->getId(),
            $e->getType(),
            $e->getMessage()
        );
    }
}
