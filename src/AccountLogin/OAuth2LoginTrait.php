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

namespace PrestaShop\Module\PsAccounts\AccountLogin;

use Employee;
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\AccountLoginException;
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\EmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\EmployeeNotFoundException;
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\Oauth2LoginException;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Repository\EmployeeAccountRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\UserInfo;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tools;

trait OAuth2LoginTrait
{
    /**
     * @return OAuth2Service
     */
    abstract protected function getOAuth2Service();

    /**
     * @param AccessToken $accessToken
     *
     * @return bool
     */
    abstract protected function initUserSession(AccessToken $accessToken);

    /**
     * @return mixed
     */
    abstract protected function redirectAfterLogin();

    /**
     * @return mixed
     */
    abstract protected function logout();

    /**
     * @return mixed
     */
    abstract protected function onLoginFailedRedirect();

    /**
     * @return SessionInterface
     */
    abstract protected function getSession();

    /**
     * @return OAuth2Session
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
     * @throws Oauth2LoginException
     * @throws \Exception
     */
    public function oauth2Login()
    {
        $shopId = Tools::getValue('shop_id', $this->getShopId() ?: \Context::getContext()->shop->id);

        /** @var ShopContext $shopContext */
        $shopContext = $this->module->getService(ShopContext::class);

        return $shopContext->execInShopContext($shopId, function () use ($shopId) {
            // FIXME: rework multishop context management
            //\Shop::setContext(\Shop::CONTEXT_SHOP, $shopId);

            $apiClient = $this->getOAuth2Service();

            //$this->getSession()->start();
            $session = $this->getSession();
            $oauth2Session = $this->getOauth2Session();

            $error = Tools::getValue('error', '');
            $state = Tools::getValue('state', '');
            $code = Tools::getValue('code', '');
            $action = Tools::getValue('action', 'login');
            $source = Tools::getValue('source', 'ps_accounts');

            if (!empty($error)) {
                // Got an error, probably user denied access
                throw new \Exception('Got error: ' . $error);
            // If we don't have an authorization code then get one
            } elseif (empty($code)) {
                // cleanup existing accessToken
                $oauth2Session->clear();

                $this->setOAuthAction($action);
                $this->setSource($source);
                $this->setShopId($shopId);

                $this->setSessionReturnTo(Tools::getValue($this->getReturnToParam()));

                $this->oauth2Redirect(Tools::getValue('locale', 'en'), $shopId);

            // Check given state against previously stored one to mitigate CSRF attack
            } elseif (empty($state) || ($session->has('oauth2state') && $state !== $session->get('oauth2state'))) {
                $session->remove('oauth2state');

                throw new \Exception('Invalid state');
            } else {
                $this->assertValidCode($code);

                try {
                    $accessToken = $apiClient->getAccessTokenByAuthorizationCode(
                        $code,
                        $this->getSession()->get('oauth2pkceCode'),
                        [],
                        [],
                        $shopId
                    );
                } catch (OAuth2Exception $e) {
                    throw new Oauth2LoginException($e->getMessage(), null, $e);
                }

                if ($this->initUserSession($accessToken)) {
                    return $this->redirectAfterLogin();
                }
            }
        });
    }

    /**
     * @param string $locale
     * @param int|null $shopId
     *
     * @return void
     *
     * @throws \Exception
     */
    private function oauth2Redirect($locale, $shopId)
    {
        $apiClient = $this->getOAuth2Service();

        $state = $apiClient->getRandomState();
        $pkceCode = $apiClient->getRandomPkceCode();

        $this->getSession()->set('oauth2state', $state);
        $this->getSession()->set('oauth2pkceCode', $pkceCode);

        $authorizationUrl = $apiClient->getAuthorizationUri(
            $state,
            $pkceCode,
            'S256',
            $locale,
            '',
            'login',
            $shopId
        );

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit;
    }

    /**
     * @param string $code
     *
     * @return void
     */
    private function assertValidCode($code)
    {
        if (!preg_match('/^[^\s\"\';\(\)]+$/', $code)) {
            throw new \InvalidArgumentException('Invalid code');
        }
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
     * @return string
     */
    private function getOAuthAction()
    {
        return $this->getSession()->get('oauth2action');
    }

    /**
     * @param string $action
     *
     * @return void
     */
    private function setOAuthAction($action)
    {
        $this->getSession()->set('oauth2action', $action);
    }

    /**
     * @return string
     */
    private function getSource()
    {
        return $this->getSession()->get('source');
    }

    /**
     * @param string $source
     *
     * @return void
     */
    private function setSource($source)
    {
        $this->getSession()->set('source', $source);
    }

    /**
     * @return string
     */
    private function getShopId()
    {
        return $this->getSession()->get('shopId');
    }

    /**
     * @param string $shopId
     *
     * @return void
     */
    private function setShopId($shopId)
    {
        $this->getSession()->set('shopId', $shopId);
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

        return $this->onLoginFailedRedirect();
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
     * @param UserInfo $user
     *
     * @return void
     */
    protected function trackEditionLoginEvent(UserInfo $user)
    {
        if ($this->module->isShopEdition()) {
            $this->getAnalyticsService()->identify(
                $user->sub,
                $user->name,
                $user->email
            );
            $this->getAnalyticsService()->group(
                $user->sub,
                (string) $this->getPsAccountsService()->getShopUuid()
            );
            $this->getAnalyticsService()->trackUserSignedIntoApp(
                $user->sub,
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
            $user->sub,
            $user->name,
            $user->email
        );
        $this->getAnalyticsService()->group(
            $user->sub,
            (string) $this->getPsAccountsService()->getShopUuid()
        );
        $this->getAnalyticsService()->trackBackOfficeSSOSignInFailed(
            $user->sub,
            $e->getType(),
            $e->getMessage()
        );
    }
}
