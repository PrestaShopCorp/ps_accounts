<?php

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PrestaShopSession
{
    const TOKEN_NAME = 'accessToken';

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var PrestaShopClientProvider
     */
    private $provider;

    public function __construct(SessionInterface $session, PrestaShopClientProvider $provider)
    {
        $this->session = $session;
        $this->provider = $provider;
    }

    /**
     * @return string|null
     *
     * @throws IdentityProviderException
     * @throws \Exception
     */
    public function getOrRefreshAccessToken()
    {
        $token = $this->getTokenProvider();
        if ($token && $token->hasExpired()) {
            /** @var AccessToken $token */
            $token = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $token->getRefreshToken(),
            ]);
            $this->setTokenProvider($token);
        }

        return $this->getAccessToken();
    }

    /**
     * @return string|null
     *
     * @throws \Exception
     */
    public function getIdToken()
    {
        $token = $this->getTokenProvider();

        return $token ? $token->getValues()['id_token'] : null;
    }

    /**
     * @return string|null
     *
     * @throws \Exception
     */
    public function getAccessToken()
    {
        $token = $this->getTokenProvider();

        return $token ? $token->getToken() : null;
    }

    /**
     * @param AccessToken $token
     *
     * @return void
     */
    public function setTokenProvider(AccessToken $token)
    {
        $this->session->set(self::TOKEN_NAME, $token);
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->session->has(self::TOKEN_NAME);
    }

    /**
     * @return PrestaShopUser
     *
     * @throws \Exception
     */
    public function getPrestashopUser()
    {
        return $this->provider->getResourceOwner($this->getTokenProvider());
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->session->remove(self::TOKEN_NAME);
    }

    /**
     * @return AccessToken|null
     *
     * @throws \Exception
     */
    private function getTokenProvider()
    {
        if (!$this->provider->getOauth2Client()->exists()) {
            $this->clear();
        }

        return $this->session->get(self::TOKEN_NAME);
    }
}
