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

use PrestaShop\Module\PsAccounts\AccountLogin\Exception\EmailNotVerifiedException;
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\EmployeeNotFoundException;
use PrestaShop\Module\PsAccounts\AccountLogin\Exception\Oauth2LoginException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\OAuth2\ApiClient;
use PrestaShop\Module\PsAccounts\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\OAuth2\Response\UserInfo;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tools;

trait OAuth2LoginTrait
{
    /**
     * @return ApiClient
     */
    abstract protected function getOAuth2Client();

    /**
     * @param UserInfo $user
     *
     * @return bool
     */
    abstract protected function initUserSession(UserInfo $user);

    /**
     * @return void
     */
    abstract protected function redirectAfterLogin();

    /**
     * @return SessionInterface
     */
    abstract protected function getSession();

    /**
     * @return OAuth2Session
     */
    abstract protected function getOauth2Session();

    /**
     * @return void
     *
     * @throws EmailNotVerifiedException
     * @throws EmployeeNotFoundException
     * @throws Oauth2LoginException
     * @throws \Exception
     */
    public function oauth2Login()
    {
        $apiClient = $this->getOAuth2Client();

        //$this->getSession()->start();
        $session = $this->getSession();
        $oauth2Session = $this->getOauth2Session();

        $error = Tools::getValue('error', '');
        $state = Tools::getValue('state', '');
        $code = Tools::getValue('code', '');

        if (!empty($error)) {
            // Got an error, probably user denied access
            throw new \Exception('Got error: ' . $error);
        // If we don't have an authorization code then get one
        } elseif (empty($code)) {
            // cleanup existing accessToken
            $oauth2Session->clear();

            $this->setSessionReturnTo(Tools::getValue($this->getReturnToParam()));

            $this->oauth2Redirect(Tools::getValue('locale', 'en'));

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
                    $apiClient->getAuthRedirectUri()
                );
            } catch (OAuth2Exception $e) {
                throw new Oauth2LoginException($e->getMessage(), null, $e);
            }

            $oauth2Session->setTokenProvider($accessToken);

            if ($this->initUserSession($oauth2Session->getUserInfo())) {
                $this->redirectAfterLogin();
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
        $apiClient = $this->getOAuth2Client();

        $state = $apiClient->getRandomState();
        $pkceCode = $apiClient->getRandomPkceCode();

        $this->getSession()->set('oauth2state', $state);
        $this->getSession()->set('oauth2pkceCode', $pkceCode);

        $authorizationUrl = $apiClient->getAuthorizationUri(
            $state,
            $pkceCode,
            'S256',
            $locale
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
}
