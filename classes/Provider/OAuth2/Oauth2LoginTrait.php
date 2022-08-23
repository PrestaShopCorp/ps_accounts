<?php

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use Tools;

trait Oauth2LoginTrait
{
    abstract protected function getProvider(): Oauth2ClientShopProvider;

    abstract protected function initUserSession(LoginData $loginData): bool;

    abstract protected function redirectAfterLogin(): void;

    abstract protected function redirectRegistrationForm(LoginData $loginData): void;

    abstract protected function startSession(): void;

    abstract protected function destroySession(): void;

    public function Oauth2Login(): void
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
                        'code' => $_GET['code'],
                    ]);
                }

                $loginData = $provider->getLoginData($_SESSION['accessToken']->getToken());

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

    private function oauth2ErrorLog(string $msg): void
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

    private function getReturnToParam(): string
    {
        return 'return_to';
    }
}
