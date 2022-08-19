<?php

namespace OAuth2\Traits;

use Customer;
use OAuth2\Client\Provider\LoginData;
use OAuth2\Client\Provider\PrestaShop;
use Tools;

trait Oauth2Login
{
    /**
     * @var PrestaShop
     */
    private $provider;

    abstract function initUserSession(LoginData $loginData): bool;

    abstract function redirectAfterLogin(): void;

    abstract function redirectRegistrationForm(LoginData $loginData): void;

    abstract function startSession();

    abstract function destroySession();

    /**
     * @return PrestaShop
     */
    private function getProvider(): PrestaShop
    {
        if (!isset($this->provider)) {
            $this->provider = PrestaShop::createShopProvider();
        }
        return $this->provider;
    }

    /**
     * @return string
     */
    private function getReturnToParam(): string
    {
        return 'return_to';
    }

    /**
     * @return void
     * https://addons.prestashop.local/login?oauth2&return_to=http://addons.prestashop.local/my-target-page-to-return-to
     */
    private function oauth2Login(): void
    {
        $provider = $this->getProvider();

        $this->startSession();

        if (!empty($_GET['error'])) {
            // Got an error, probably user denied access
            $this->oauth2ErrorLog('Got error: ' . $_GET['error']);

            $this->oauth2Redirect();

            // If we don't have an authorization code then get one
        } elseif (!isset($_GET['code'])) {
            // cleanup existing accessToken
            $_SESSION['accessToken'] = null;

            $this->setSessionReturnTo(Tools::getValue($this->getReturnToParam()));

            $this->oauth2Redirect();

            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }

            $this->oauth2ErrorLog('Invalid state');

            $this->oauth2Redirect();
        } else {
            try {
                if (!isset($_SESSION['accessToken'])) {
                    // Try to get an access token using the authorization code grant.
                    $_SESSION['accessToken'] = $provider->getAccessToken('authorization_code', [
                        'code' => $_GET['code']
                    ]);
                }

                $loginData = $provider::getLoginData($_SESSION['accessToken']->getToken());

                if ($this->initUserSession($loginData)) {
                    $this->redirectAfterLogin();
                } else {
                    $this->redirectRegistrationForm($loginData);
                }

            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                // Failed to get the access token or user details.
                $this->oauth2ErrorLog($e->getMessage());

                $this->oauth2Redirect();
            }
        }
    }

    private function oauth2Redirect(): void
    {
        $provider = $this->getProvider();

        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $provider->getAuthorizationUrl();

        // Get the state generated for you and store it to the session.
        $_SESSION['oauth2state'] = $provider->getState();

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit;
    }

    private function oauth2ErrorLog(string $msg)
    {
        error_log('[OAuth2] ' . $msg);
    }

    private function getSessionReturnTo(): string
    {
        if (isset($_SESSION[$this->getReturnToParam()])) {
            return $_SESSION[$this->getReturnToParam()];
        }
        return '';
    }

    private function setSessionReturnTo(string $returnTo): void
    {
        $_SESSION[$this->getReturnToParam()] = $returnTo;
    }
}
