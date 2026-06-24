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
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\InvalidOAuth2StateException;
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
     * @return string
     */
    abstract protected function getSignupUrl();

    /**
     * Output a same-origin HTML page that immediately re-navigates to the given
     * URL. Used to recover from a lost session on the OAuth2 callback when
     * PS_COOKIE_SAMESITE=Strict prevents the cookie from being sent on the
     * cross-site return (see buildBounceUrl()).
     *
     * @param string $url
     *
     * @return mixed
     */
    abstract protected function renderSameSiteBounce($url);

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

            $returnTo = Tools::getValue($this->getReturnToParam());
            $error = Tools::getValue('error', '');
            $state = Tools::getValue('state', '');
            $code = Tools::getValue('code', '');
            $action = Tools::getValue('action', 'login');
            $source = Tools::getValue('source', 'ps_accounts');
            $forceSignup = Tools::getValue('forceSignup', false);

            if (!empty($error)) {
                // Got an error, probably user denied access
                throw new \Exception('Got error: ' . $error);
            // If we don't have an authorization code then get one
            } elseif (empty($code)) {
                // cleanup existing accessToken
                $oauth2Session->clear();

                $this->setReturnTo($returnTo);
                $this->setOAuthAction($action);
                $this->setSource($source);
                $this->setShopId($shopId);
                $this->setForceSignup($forceSignup);

                $this->oauth2Redirect(Tools::getValue('locale', 'en'), $shopId);
            } else {
                // We have an authorization code: validate the callback state and
                // exchange it for a token (or recover a lost session via bounce).
                return $this->handleAuthorizationCode($apiClient, $session, $state, $code, $shopId);
            }
        });
    }

    /**
     * Validate the OAuth2 callback state then exchange the authorization code.
     *
     * @param OAuth2Service $apiClient
     * @param SessionInterface $session
     * @param string $state
     * @param string $code
     * @param int|null $shopId
     *
     * @return mixed bounce response, post-login redirect, or null
     *
     * @throws InvalidOAuth2StateException
     * @throws Oauth2LoginException
     * @throws EmailNotVerifiedException
     * @throws EmployeeNotFoundException
     */
    private function handleAuthorizationCode($apiClient, $session, $state, $code, $shopId)
    {
        // No state at all in the callback: nothing to validate against, reject.
        if (empty($state)) {
            $this->rejectInvalidState($session);
        }

        // Session lost on the callback: oauth2state (and pkceCode) are gone. The
        // most common cause is PS_COOKIE_SAMESITE=Strict — the browser withholds
        // the admin cookie on the cross-site redirect back from auth-hydra, so the
        // session arrives empty. Re-issuing the very same callback as a SAME-SITE
        // navigation makes the browser send the cookie and restores the session.
        if (!$session->has('oauth2state')) {
            if (!$this->hasAlreadyBounced()) {
                return $this->renderSameSiteBounce($this->buildBounceUrl());
            }
            // Already bounced once and the session is still missing: this is a
            // genuine session loss (expired, cookies disabled, ...), fail clean.
            $this->rejectInvalidState($session);
        }

        // Check given state against previously stored one to mitigate CSRF attack.
        if ($state !== $session->get('oauth2state')) {
            $this->rejectInvalidState($session);
        }

        $this->assertValidCode($code);

        try {
            $accessToken = $apiClient->getAccessTokenByAuthorizationCode(
                $code,
                $session->get('oauth2pkceCode'),
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

        return null;
    }

    /**
     * Drop the stored state and reject the callback. Removing an absent key is a
     * no-op, so this is safe to call when the session was already lost.
     *
     * @param SessionInterface $session
     *
     * @return void
     *
     * @throws InvalidOAuth2StateException
     */
    private function rejectInvalidState($session)
    {
        $session->remove('oauth2state');

        throw new InvalidOAuth2StateException();
    }

    /**
     * Whether the current callback is already the result of a same-site bounce.
     * Guards against an infinite redirect loop when the session is genuinely lost.
     *
     * @return bool
     */
    private function hasAlreadyBounced()
    {
        return (bool) Tools::getValue('__ps_oauth_retry');
    }

    /**
     * Rebuild the current callback URL, preserving every query param (code,
     * state, action, ...) and adding the one-shot bounce marker. The returned
     * URL is same-origin, so navigating to it client-side is a same-site
     * request that carries the (Strict) admin cookie.
     *
     * @return string
     */
    private function buildBounceUrl()
    {
        $params = $_GET;
        $params['__ps_oauth_retry'] = '1';

        $path = strtok($_SERVER['REQUEST_URI'], '?');

        return $path . '?' . http_build_query($params);
    }

    /**
     * Minimal same-origin HTML page that re-navigates to $url. location.replace
     * avoids polluting the history; the <noscript> meta-refresh is a fallback.
     * $url is escaped for both the JS string and the HTML attribute contexts
     * because it carries attacker-influenceable query params (code/state).
     *
     * @param string $url
     *
     * @return string
     */
    private function buildBounceHtml($url)
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<noscript><meta http-equiv="refresh" content="0;url='
            . htmlspecialchars($url, ENT_QUOTES) . '"></noscript>'
            . '<script type="text/javascript">window.location.replace('
            . json_encode($url) . ');</script></head><body></body></html>';
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
    private function getReturnTo()
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
    private function setReturnTo($returnTo)
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
     * @return bool
     */
    private function getForceSignup()
    {
        return (bool) $this->getSession()->get('forceSignup', false);
    }

    /**
     * @param bool $forceSignup
     *
     * @return void
     */
    private function setForceSignup($forceSignup)
    {
        $this->getSession()->set('forceSignup', $forceSignup);
    }

    /**
     * Remove only the transient OAuth2 keys from the session, leaving every other
     * attribute untouched. On PS 1.7+ getSession() is the core BO session, so a
     * full clear() would sign the employee out at the end of the point-of-contact
     * flow; clearing just our own keys avoids that.
     *
     * @return void
     */
    private function clearOAuth2SessionState()
    {
        $session = $this->getSession();

        $session->remove('oauth2state');
        $session->remove('oauth2pkceCode');
        $session->remove('oauth2action');
        $session->remove('source');
        $session->remove('shopId');
        $session->remove('forceSignup');
        $session->remove($this->getReturnToParam());
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
