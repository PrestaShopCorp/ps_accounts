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

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tools;

trait PrestaShopLoginTrait
{
    abstract protected function getProvider(): PrestaShopClientProvider;

    abstract protected function initUserSession(PrestaShopUser $user): bool;

    abstract protected function redirectAfterLogin(): void;

    abstract protected function getSession(): SessionInterface;

    abstract protected function getOauth2Session(): PrestaShopSession;

    /**
     * @throws IdentityProviderException
     * @throws \Exception
     */
    public function oauth2Login(): void
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
            // Try to get an access token using the authorization code grant.
            /** @var AccessToken $accessToken */
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code'],
            ]);
            $oauth2Session->setTokenProvider($accessToken);

            if ($this->initUserSession($oauth2Session->getPrestashopUser())) {
                $this->redirectAfterLogin();
            }
        }
    }

    private function oauth2Redirect(string $locale): void
    {
        $provider = $this->getProvider();

        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $provider->getAuthorizationUrl(['ui_locales' => $locale]);

        // Get the state generated for you and store it to the session.
        $this->getSession()->set('oauth2state', $provider->getState());

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit;
    }

    private function oauth2ErrorLog(string $msg): void
    {
        Logger::getInstance()->error('[OAuth2] ' . $msg);
    }

    private function getSessionReturnTo(): string
    {
        return $this->getSession()->get($this->getReturnToParam(), '');
    }

    private function setSessionReturnTo(string $returnTo): void
    {
        $this->getSession()->set($this->getReturnToParam(), $returnTo);
    }

    private function getReturnToParam(): string
    {
        return 'return_to';
    }
}
