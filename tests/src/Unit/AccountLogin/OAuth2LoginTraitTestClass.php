<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\AccountLogin;

use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2LoginTrait;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;

/**
 * Concrete host exposing OAuth2LoginTrait so it can be exercised in unit tests.
 *
 * Collaborators returned by the trait's abstract methods are injected through the
 * constructor so each test can supply its own doubles.
 */
class OAuth2LoginTraitTestClass
{
    use OAuth2LoginTrait;

    public $module;

    private $session;

    private $oauth2Service;

    private $oauth2Session;

    /**
     * Records the URL passed to renderSameSiteBounce() so tests can assert on it
     * (the real controllers echo+exit / return a Response instead).
     *
     * @var string|null
     */
    public $bouncedUrl = null;

    public function __construct($module, $session, $oauth2Service, $oauth2Session)
    {
        $this->module = $module;
        $this->session = $session;
        $this->oauth2Service = $oauth2Service;
        $this->oauth2Session = $oauth2Session;
    }

    protected function renderSameSiteBounce($url)
    {
        $this->bouncedUrl = $url;

        return 'same-site-bounce';
    }

    protected function getOAuth2Service()
    {
        return $this->oauth2Service;
    }

    protected function initUserSession(AccessToken $accessToken)
    {
        return false;
    }

    protected function redirectAfterLogin()
    {
        return 'redirect-after-login';
    }

    protected function logout()
    {
        return null;
    }

    protected function onLoginFailedRedirect()
    {
        return 'login-failed-redirect';
    }

    protected function getSession()
    {
        return $this->session;
    }

    protected function getOauth2Session()
    {
        return $this->oauth2Session;
    }

    protected function getAnalyticsService()
    {
        return null;
    }

    protected function getPsAccountsService()
    {
        return null;
    }

    protected function getSignupUrl()
    {
        return '';
    }
}
